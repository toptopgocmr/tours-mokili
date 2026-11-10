<?php

namespace App\Services\Flights\Schedules;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Programme des vols en direct via AIRLABS (plan gratuit) : departs et
 * arrivees d'un aeroport (heures prevues / estimees, statut, retard).
 * Cle : AIRLABS_API_KEY. Reponses mises en cache (30 min par defaut) pour
 * rester dans le quota gratuit.
 */
class AirLabsSchedules
{
    public static function configured(): bool
    {
        return ! empty(config('flights.airlabs.key'));
    }

    /**
     * @param  string  $direction  "departures" | "arrivals"
     * @return array{flights: array, error: ?string}
     */
    public function board(string $airport, string $direction = 'departures', ?string $other = null): array
    {
        if (! self::configured()) {
            return ['flights' => [], 'error' => 'not_configured'];
        }
        $airport = strtoupper($airport);
        $other = $other ? strtoupper($other) : null;
        $params = $direction === 'arrivals'
            ? array_filter(['arr_iata' => $airport, 'dep_iata' => $other])
            : array_filter(['dep_iata' => $airport, 'arr_iata' => $other]);

        try {
            $rows = Cache::remember('airlabs:'.md5(json_encode($params)), now()->addMinutes((int) config('flights.airlabs.cache_minutes', 30)), function () use ($params) {
                $res = Http::baseUrl(config('flights.airlabs.endpoint', 'https://airlabs.co/api/v9'))
                    ->acceptJson()
                    ->timeout((int) config('flights.airlabs.timeout', 15))
                    ->get('/schedules', $params + ['api_key' => config('flights.airlabs.key')]);
                if (! $res->successful() || $res->json('error')) {
                    throw new \RuntimeException('AirLabs : '.($res->json('error.message') ?? 'HTTP '.$res->status()));
                }

                return $res->json('response') ?? [];
            });
        } catch (Throwable $e) {
            Log::warning('AirLabs schedules failed', ['error' => $e->getMessage()]);

            return ['flights' => [], 'error' => 'unavailable'];
        }

        // Les vols en partage de code (meme avion) n'apparaissent qu'une fois.
        $flights = collect($rows)
            ->reject(fn ($r) => ! empty($r['cs_flight_iata']))
            ->map(fn ($r) => [
                'flight' => $r['flight_iata'] ?? trim(($r['airline_iata'] ?? '').($r['flight_number'] ?? '')),
                'airline_code' => $r['airline_iata'] ?? null,
                'origin' => $r['dep_iata'] ?? null,
                'destination' => $r['arr_iata'] ?? null,
                'departure_at' => $r['dep_time'] ?? null,
                'departure_estimated' => $r['dep_estimated'] ?? null,
                'arrival_at' => $r['arr_time'] ?? null,
                'arrival_estimated' => $r['arr_estimated'] ?? null,
                'terminal' => $direction === 'arrivals' ? ($r['arr_terminal'] ?? null) : ($r['dep_terminal'] ?? null),
                'gate' => $direction === 'arrivals' ? ($r['arr_gate'] ?? null) : ($r['dep_gate'] ?? null),
                'duration_minutes' => isset($r['duration']) ? (int) $r['duration'] : null,
                'delay_minutes' => (int) ($direction === 'arrivals' ? ($r['arr_delayed'] ?? 0) : ($r['dep_delayed'] ?? 0)),
                'status' => $r['status'] ?? 'scheduled',
            ])
            ->sortBy(fn ($f) => $direction === 'arrivals' ? $f['arrival_at'] : $f['departure_at'])
            ->values()->all();

        return ['flights' => $flights, 'error' => null];
    }
}
