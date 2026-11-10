<?php

namespace App\Services\Voyage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Horaires des trains, bus, metros, trams et ferries du MONDE entier via
 * TRANSITLAND (transit.land, donnees GTFS / GTFS-RT de milliers de
 * reseaux). Cle : TRANSITLAND_API_KEY (plan gratuit Explorer).
 *
 *  - stations('Paris Gare de Lyon') : recherche de gares / arrets par nom ;
 *  - departures('f-u09-...') : prochains departs d'une gare (heure locale,
 *    retard temps reel quand le reseau le publie).
 *
 * Reponses mises en cache pour rester dans le quota gratuit.
 */
class WorldTransitBoard
{
    /** Familles de transport (codes GTFS route_type, simples et etendus). */
    public const FAMILIES = [
        'train' => 'Train',
        'bus' => 'Bus / car',
        'metro' => 'Metro / tram',
        'ferry' => 'Ferry / bateau',
    ];

    public static function configured(): bool
    {
        return ! empty(config('services.transitland.key'));
    }

    public static function family(?int $type): string
    {
        return match (true) {
            $type === null => 'bus',
            $type === 2, $type >= 100 && $type < 200 => 'train',
            $type === 3, $type === 11, $type >= 200 && $type < 300, $type >= 700 && $type < 800 => 'bus',
            $type === 4, $type >= 1000 && $type < 1300 => 'ferry',
            default => 'metro', // 0 tram, 1 metro, 5-7, 12, 400-900...
        };
    }

    /** @return array{stations: array, error: ?string} */
    public function stations(string $query): array
    {
        $query = trim($query);
        if (! self::configured()) {
            return ['stations' => [], 'error' => 'not_configured'];
        }
        if (mb_strlen($query) < 2) {
            return ['stations' => [], 'error' => null];
        }

        try {
            $rows = Cache::remember('tl:stops:'.md5(mb_strtolower($query)), now()->addDay(), function () use ($query) {
                return $this->get('/stops', ['search' => $query, 'limit' => 25])['stops'] ?? [];
            });
        } catch (Throwable $e) {
            Log::warning('Transitland stops failed', ['error' => $e->getMessage()]);

            return ['stations' => [], 'error' => 'unavailable'];
        }

        // Gares (location_type 1) d'abord, puis arrets ; un seul resultat par nom + pays.
        $stations = collect($rows)
            ->filter(fn ($s) => in_array((int) ($s['location_type'] ?? 0), [0, 1], true) && ! empty($s['onestop_id']))
            ->sortBy(fn ($s) => (int) ($s['location_type'] ?? 0) === 1 ? 0 : 1)
            ->unique(fn ($s) => mb_strtolower(($s['stop_name'] ?? '').'|'.($s['place']['adm0_iso'] ?? '')))
            ->take(10)
            ->map(fn ($s) => [
                'id' => $s['onestop_id'],
                'name' => $s['stop_name'] ?? '',
                'kind' => (int) ($s['location_type'] ?? 0) === 1 ? 'Gare' : 'Arret',
                'region' => $s['place']['adm1_name'] ?? null,
                'country' => $s['place']['adm0_name'] ?? null,
                'country_code' => $s['place']['adm0_iso'] ?? null,
            ])
            ->values()->all();

        return ['stations' => $stations, 'error' => null];
    }

    /**
     * @param  string  $family  '' | train | bus | metro | ferry
     * @return array{station: ?array, departures: array, error: ?string}
     */
    public function departures(string $stopId, string $family = '', ?string $date = null): array
    {
        if (! self::configured()) {
            return ['station' => null, 'departures' => [], 'error' => 'not_configured'];
        }
        if (! preg_match('/^[A-Za-z0-9:_~.\-]{3,200}$/', $stopId)) {
            return ['station' => null, 'departures' => [], 'error' => 'invalid'];
        }

        $params = array_filter([
            'limit' => 80,
            'include_alerts' => 'false',
            // Aujourd'hui : les 6 prochaines heures ; autre jour : toute la journee.
            'next' => $date ? null : 6 * 3600,
            'date' => $date,
            'start_time' => $date ? '00:00:00' : null,
            'end_time' => $date ? '23:59:59' : null,
        ], fn ($v) => $v !== null);

        try {
            $data = Cache::remember('tl:dep:'.md5($stopId.json_encode($params)), now()->addMinutes(2), function () use ($stopId, $params) {
                return $this->get('/stops/'.rawurlencode($stopId).'/departures', $params);
            });
        } catch (Throwable $e) {
            Log::warning('Transitland departures failed', ['stop' => $stopId, 'error' => $e->getMessage()]);

            return ['station' => null, 'departures' => [], 'error' => 'unavailable'];
        }

        $stops = $data['stops'] ?? [];
        $first = $stops[0] ?? null;
        $rows = collect($stops)->flatMap(fn ($s) => collect($s['departures'] ?? [])->map(fn ($d) => $d + ['_stop' => $s]));

        $departures = $rows->map(function ($d) {
            $route = $d['trip']['route'] ?? [];
            $type = isset($route['route_type']) ? (int) $route['route_type'] : null;
            $sched = $d['departure']['scheduled'] ?? $d['departure_time'] ?? null;
            $est = $d['departure']['estimated'] ?? null;
            $delay = $d['departure']['estimated_delay'] ?? $d['departure']['delay'] ?? null;
            $color = $route['route_color'] ?? null;

            return [
                'time' => $sched ? substr($sched, 0, 5) : null,
                'estimated' => $est ? substr($est, 0, 5) : null,
                'delay_minutes' => $delay !== null ? (int) round(((int) $delay) / 60) : null,
                'date' => $d['date'] ?? $d['service_date'] ?? null,
                'line' => ($route['route_short_name'] ?? '') ?: (($d['trip']['trip_short_name'] ?? '') ?: ($route['route_long_name'] ?? '')),
                'line_name' => $route['route_long_name'] ?? null,
                'color' => $color ? '#'.ltrim($color, '#') : null,
                'text_color' => ! empty($route['route_text_color']) ? '#'.ltrim($route['route_text_color'], '#') : null,
                'family' => self::family($type),
                'headsign' => ($d['stop_headsign'] ?? '') ?: ($d['trip']['trip_headsign'] ?? ''),
                'operator' => $route['agency']['agency_name'] ?? null,
                'platform' => $d['_stop']['platform_code'] ?? null,
                'cancelled' => ($d['schedule_relationship'] ?? '') === 'CANCELED' || ($d['schedule_relationship'] ?? '') === 'CANCELLED',
                'realtime' => $est !== null,
            ];
        })
            ->when($family !== '', fn ($c) => $c->where('family', $family))
            ->sortBy(fn ($d) => ($d['date'] ?? '').' '.($d['time'] ?? ''))
            ->values()->all();

        return [
            'station' => $first ? [
                'id' => $first['onestop_id'] ?? $stopId,
                'name' => $first['stop_name'] ?? '',
                'country' => $first['place']['adm0_name'] ?? null,
                'timezone' => $first['stop_timezone'] ?? null,
            ] : null,
            'departures' => $departures,
            'error' => null,
        ];
    }

    protected function get(string $path, array $params): array
    {
        $res = Http::baseUrl(config('services.transitland.endpoint', 'https://transit.land/api/v2/rest'))
            ->withHeaders(['apikey' => config('services.transitland.key')])
            ->acceptJson()
            ->timeout((int) config('services.transitland.timeout', 15))
            ->get($path, $params);

        if (! $res->successful()) {
            throw new \RuntimeException('Transitland HTTP '.$res->status().' '.substr($res->body(), 0, 200));
        }

        return $res->json() ?? [];
    }
}
