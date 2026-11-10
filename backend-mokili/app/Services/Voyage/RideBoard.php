<?php

namespace App\Services\Voyage;

use App\Models\Voyage\TravelOffer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Programme des departs / arrivees des trains, bus, canots rapides et taxis
 * (panneau facon gare routiere), construit a partir des trajets saisis dans
 * l'admin (Voyage > Circuit / trajet). Statut calcule en direct : prevu,
 * embarquement, en route, arrive, complet.
 *
 * Les horaires sont saisis en heure du Congo et stockes tels quels : on
 * compare donc avec l'heure de Brazzaville "naive" (meme convention que le site).
 */
class RideBoard
{
    public const MODES = ['train', 'bus', 'canot', 'taxi'];

    /** Villes proposees par defaut (en plus de celles des trajets saisis). */
    public const DEFAULT_CITIES = ['Brazzaville', 'Pointe-Noire', 'Dolisie', 'Kinshasa', 'Oyo', 'Owando', 'Ouesso', 'Nkayi', 'Impfondo', 'Mossaka', 'Kinkala', 'Gamboma'];

    public function now(): CarbonInterface
    {
        return Carbon::parse(now('Africa/Brazzaville')->format('Y-m-d H:i:s'), config('app.timezone'));
    }

    /** @return array{city: string, direction: string, mode: string, date: string, rides: array, cities: array} */
    public function board(Request $request): array
    {
        $now = $this->now();
        $city = trim((string) $request->query('city', 'Brazzaville')) ?: 'Brazzaville';
        $direction = $request->query('direction') === 'arrivals' ? 'arrivals' : 'departures';
        $mode = in_array($request->query('mode'), self::MODES, true) ? (string) $request->query('mode') : '';
        $date = $this->date($request->query('date'), $now);

        $rides = TravelOffer::query()->active()
            ->where('type', 'circuit')
            ->whereIn('transport_mode', $mode ? [$mode] : self::MODES)
            ->where($direction === 'arrivals' ? 'destination_city' : 'origin_city', 'like', '%'.$city.'%')
            ->when($direction === 'arrivals',
                fn ($q) => $q->where(fn ($w) => $w->whereDate('arrival_at', $date)->orWhere(fn ($x) => $x->whereNull('arrival_at')->whereDate('departure_at', $date))),
                fn ($q) => $q->whereDate('departure_at', $date))
            ->orderBy($direction === 'arrivals' ? 'arrival_at' : 'departure_at')
            ->limit(150)
            ->get()
            ->map(fn (TravelOffer $o) => [
                'id' => $o->id,
                'slug' => $o->slug,
                'mode' => $o->transport_mode,
                'mode_label' => $o->transport_mode_label,
                'operator' => $o->airline,
                'origin' => $o->origin_city,
                'destination' => $o->destination_city,
                'departure_point' => $o->departure_point,
                'departure_at' => $o->departure_at?->format('Y-m-d\TH:i'),
                'arrival_at' => $o->arrival_at?->format('Y-m-d\TH:i'),
                'seats' => (int) $o->seats_available,
                'price' => round((float) $o->discounted_price),
                'currency' => $o->currency,
                'status' => $this->status($o, $now),
                'bookable' => $o->departure_at && $o->departure_at->greaterThan($now) && $o->seats_available > 0,
            ])
            ->values()
            ->all();

        return [
            'city' => $city,
            'direction' => $direction,
            'mode' => $mode,
            'date' => $date,
            'rides' => $rides,
            'cities' => $this->cities(),
        ];
    }

    /** prevu | boarding | en_route | arrived | departed | full */
    public function status(TravelOffer $o, CarbonInterface $now): string
    {
        $dep = $o->departure_at;
        $arr = $o->arrival_at;
        if (! $dep) {
            return 'scheduled';
        }
        if ($arr && $now->greaterThanOrEqualTo($arr)) {
            return 'arrived';
        }
        if ($now->greaterThanOrEqualTo($dep)) {
            return $arr ? 'en_route' : 'departed';
        }
        if ($now->diffInMinutes($dep) <= 30) {
            return 'boarding';
        }

        return (int) $o->seats_available <= 0 ? 'full' : 'scheduled';
    }

    /** Villes de depart / arrivee des trajets actifs + villes principales. */
    public function cities(): array
    {
        $fromRides = TravelOffer::query()->active()->where('type', 'circuit')->whereIn('transport_mode', self::MODES)
            ->get(['origin_city', 'destination_city'])
            ->flatMap(fn ($o) => [$o->origin_city, $o->destination_city]);

        return collect(self::DEFAULT_CITIES)->merge($fromRides)->filter()->map(fn ($c) => trim($c))
            ->unique(fn ($c) => mb_strtolower($c))->sort(fn ($a, $b) => $this->rank($a) <=> $this->rank($b) ?: strcmp($a, $b))
            ->values()->all();
    }

    protected function rank(string $c): int
    {
        $i = array_search($c, ['Brazzaville', 'Pointe-Noire', 'Dolisie', 'Kinshasa'], true);

        return $i === false ? 99 : $i;
    }

    protected function date(mixed $value, CarbonInterface $now): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $now->toDateString();
    }
}
