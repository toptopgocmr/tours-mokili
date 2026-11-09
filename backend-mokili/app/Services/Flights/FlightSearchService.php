<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Models\Flights\FlightSupplierLog;
use App\Models\User;
use App\Services\Flights\Data\SearchCriteria;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recherche multi-fournisseurs : interroge chaque fournisseur actif,
 * normalise les offres, applique la marge et fixe leur expiration.
 */
class FlightSearchService
{
    public function __construct(
        protected FlightSupplierManager $suppliers,
        protected FlightPricing $pricing,
    ) {}

    public function search(array $params, ?User $user = null): FlightSearch
    {
        $criteria = SearchCriteria::fromArray($params);

        $attributes = $criteria->toArray();
        if ($attributes['legs'] === null) {
            unset($attributes['legs']); // colonne remplie seulement en multi-destinations
        }
        $search = FlightSearch::create($attributes + ['user_id' => $user?->id]);

        $errors = [];
        $count = 0;
        $max = (int) config('flights.max_offers', 60);

        foreach ($this->suppliers->activeSuppliers() as $supplier) {
            $started = microtime(true);
            try {
                $offers = $this->suppliers->driver($supplier)->search($criteria);
            } catch (Throwable $e) {
                // Un fournisseur indisponible ne bloque pas les autres.
                $errors[$supplier->code] = 'Fournisseur momentanement indisponible.';
                Log::warning('Flight supplier search failed', ['supplier' => $supplier->code, 'error' => $e->getMessage()]);
                FlightSupplierLog::create([
                    'flight_supplier_id' => $supplier->id, 'operation' => 'search', 'success' => false,
                    'outcome' => 'error', 'request' => $criteria->toArray(),
                    'response' => ['error' => mb_substr($e->getMessage(), 0, 300)],
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                ]);

                continue;
            }

            foreach ($offers as $data) {
                if ($count >= $max) {
                    break;
                }
                $quote = $this->pricing->quote($data);

                FlightOffer::create([
                    'flight_search_id' => $search->id,
                    'flight_supplier_id' => $supplier->id,
                    'supplier_offer_id' => $data->supplierOfferId,
                    'validating_airline' => $data->validatingAirline,
                    'airline_name' => $data->airlineName,
                    'slices' => $data->slices,
                    'passengers' => $this->displayLines($data, $quote),
                    'fare_rules' => $data->fareRules,
                    'currency' => $data->currency,
                    'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 30)),
                    'raw' => ['supplier_lines' => $data->passengers, 'ticketing_deadline' => $data->ticketingDeadline] + $data->raw,
                ] + $quote);
                $count++;
            }
        }

        $search->update(['results_count' => $count, 'supplier_errors' => $errors ?: null]);

        return $search;
    }

    /** Lignes par passager affichees (prix client, sans le cout fournisseur). */
    public function displayLines($data, array $quote): array
    {
        return array_map(
            fn ($l) => ['type' => $l['type'], 'count' => $l['count'], 'unit_amount' => $l['display_amount']],
            $this->pricing->passengerLines($data, $quote),
        );
    }

    /** Offres d'une recherche, triees et filtrees pour l'affichage. */
    public function offersFor(FlightSearch $search, array $filters = [])
    {
        $offers = $search->offers()->get();

        if (isset($filters['stops']) && $filters['stops'] !== '') {
            $max = (int) $filters['stops'];
            $offers = $offers->filter(fn (FlightOffer $o) => collect($o->slices)->max('stops') <= $max);
        }
        if (! empty($filters['airline'])) {
            $offers = $offers->where('validating_airline', $filters['airline']);
        }

        $sort = $filters['sort'] ?? 'best';
        $duration = fn (FlightOffer $o) => (int) collect($o->slices)->sum('duration_minutes');

        if ($sort === 'best') {
            // "Les meilleurs" (comme Skyscanner) : equilibre prix / duree / escales.
            $minPrice = max(1.0, (float) $offers->min(fn ($o) => (float) $o->total_amount));
            $minDuration = max(1, (int) $offers->min($duration));

            return $offers->sortBy(fn (FlightOffer $o) => (float) $o->total_amount / $minPrice
                + 0.7 * $duration($o) / $minDuration
                + 0.05 * collect($o->slices)->sum('stops'))->values();
        }

        return $offers->sortBy(fn (FlightOffer $o) => match ($sort) {
            'duration' => $duration($o),
            'departure' => $o->slices[0]['departure_at'] ?? '',
            default => (float) $o->total_amount,
        })->values();
    }
}
