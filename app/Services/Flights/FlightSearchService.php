<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Models\User;
use App\Services\Flights\Suppliers\FlightSupplierManager;
use Illuminate\Validation\ValidationException;
use Throwable;

class FlightSearchService
{
    public function __construct(protected FlightSupplierManager $suppliers) {}

    /** Prix client = cout fournisseur + marge Mokili. */
    public static function customerPrice(float $cost): float
    {
        return round($cost * (1 + (float) config('flights.margin_percent', 0) / 100), 0);
    }

    public function search(?User $user, array $criteria): FlightSearch
    {
        $legs = null;
        if (! empty($criteria['legs']) && is_array($criteria['legs'])) {
            $legs = array_values(array_map(fn ($l) => [
                'origin' => strtoupper((string) $l['origin']),
                'destination' => strtoupper((string) $l['destination']),
                'date' => (string) $l['date'],
            ], $criteria['legs']));

            for ($i = 1; $i < count($legs); $i++) {
                if ($legs[$i]['date'] < $legs[$i - 1]['date']) {
                    throw ValidationException::withMessages(['legs' => 'Les dates des vols doivent etre dans l\'ordre chronologique.']);
                }
            }
            $criteria['origin'] = $legs[0]['origin'];
            $criteria['destination'] = $legs[0]['destination'];
            $criteria['depart_date'] = $legs[0]['date'];
            $criteria['return_date'] = null;
        }
        $direct = (bool) ($criteria['direct_only'] ?? false);

        $search = FlightSearch::create([
            'user_id' => $user?->id,
            'origin' => strtoupper($criteria['origin']),
            'destination' => strtoupper($criteria['destination']),
            'depart_date' => $criteria['depart_date'],
            'return_date' => $criteria['return_date'] ?? null,
            'legs' => $legs,
            'direct_only' => $direct,
            'adults' => (int) ($criteria['adults'] ?? 1),
            'children' => (int) ($criteria['children'] ?? 0),
            'infants' => (int) ($criteria['infants'] ?? 0),
            'cabin' => $criteria['cabin'] ?? 'economy',
        ]);

        $errors = [];
        $count = 0;

        foreach ($this->suppliers->active() as $supplier) {
            try {
                $driver = $this->suppliers->driver($supplier);

                foreach ($driver->search($search) as $offer) {
                    if ($direct && ! self::isDirect($offer['itinerary'])) {
                        continue; // "Vols directs uniquement"
                    }

                    FlightOffer::create([
                        'flight_search_id' => $search->id,
                        'flight_supplier_id' => $supplier->id,
                        'external_id' => $offer['external_id'],
                        'itinerary' => $offer['itinerary'],
                        'airline_code' => $offer['airline_code'],
                        'airline_name' => $offer['airline_name'],
                        'price_total' => self::customerPrice($offer['cost']),
                        'supplier_cost' => $offer['cost'],
                        'currency' => $offer['currency'],
                        'fare_rules' => $offer['fare_rules'],
                        'raw' => $offer['raw'] ?? null,
                        'expires_at' => $offer['expires_at'],
                    ]);
                    $count++;
                }

                $supplier->update(['connection_status' => 'ok', 'last_checked_at' => now()]);
            } catch (Throwable $e) {
                // Un fournisseur indisponible ne doit pas casser toute la recherche.
                report($e);
                $errors[] = ['supplier' => $supplier->code, 'message' => 'Fournisseur momentanement indisponible.'];
                $supplier->update(['connection_status' => 'down', 'last_checked_at' => now()]);
            }
        }

        $search->update(['results_count' => $count, 'supplier_errors' => $errors ?: null]);
        FlightAudit::log('search.performed', null, [
            'search_id' => $search->id, 'route' => $search->origin.'-'.$search->destination, 'results' => $count,
        ], $user ? 'user:'.$user->id : 'guest');

        return $search;
    }

    /** Vrai si aucun trajet de l'itineraire n'a d'escale (un seul segment, 0 escale). */
    public static function isDirect(array $itinerary): bool
    {
        $legs = $itinerary['legs'] ?? array_values(array_filter([$itinerary['outbound'] ?? [], $itinerary['inbound'] ?? []]));

        foreach ($legs as $segments) {
            if (count($segments) !== 1 || (int) ($segments[0]['stops'] ?? 0) > 0) {
                return false;
            }
        }

        return true;
    }
}
