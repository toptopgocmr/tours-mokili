<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightSearch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Fournisseur de SIMULATION (sandbox) : genere des offres deterministes et
 * rejoue les scenarios d'incident du cahier de recette (refus, echec
 * d'emission, timeout apres emission, changement de prix). Son etat est
 * garde en cache pour que findBooking()/findTickets() repondent comme un
 * vrai fournisseur. A remplacer par le connecteur reel.
 */
class FakeFlightSupplier implements FlightSupplierDriver
{
    private const AIRLINES = [
        ['AF', 'Air France'], ['ET', 'Ethiopian Airlines'], ['SN', 'Brussels Airlines'],
        ['KQ', 'Kenya Airways'], ['TK', 'Turkish Airlines'], ['QR', 'Qatar Airways'],
        ['EK', 'Emirates'], ['MS', 'EgyptAir'],
    ];

    public function search(FlightSearch $search): array
    {
        if (! empty($search->legs)) {
            return $this->searchMulti($search);
        }

        $offers = [];
        $seed = crc32($search->origin.$search->destination.$search->depart_date->format('Y-m-d'));
        $base = 90000 + ($seed % 400000); // XAF par adulte

        foreach (self::AIRLINES as $i => [$code, $name]) {
            if (($seed + $i) % 5 === 0) {
                continue; // quelques compagnies ne desservent pas la ligne
            }

            $depHour = 5 + (($seed >> $i) % 16);
            $duration = 120 + (($seed + $i * 37) % 600);
            $stops = ($seed + $i) % 3 === 0 ? 1 : 0;
            $depart = $search->depart_date->copy()->setTime($depHour, ($i * 15) % 60);
            $arrive = $depart->copy()->addMinutes($duration + $stops * 90);

            $segments = [[
                'from' => $search->origin,
                'to' => $search->destination,
                'flight_number' => $code.(100 + (($seed + $i * 13) % 800)),
                'departure' => $depart->toIso8601String(),
                'arrival' => $arrive->toIso8601String(),
                'duration_minutes' => $duration + $stops * 90,
                'stops' => $stops,
            ]];

            $itinerary = ['outbound' => $segments, 'inbound' => []];

            if ($search->return_date) {
                $rDepart = $search->return_date->copy()->setTime(8 + $i, 30);
                $itinerary['inbound'] = [[
                    'from' => $search->destination,
                    'to' => $search->origin,
                    'flight_number' => $code.(900 - (($seed + $i * 7) % 700)),
                    'departure' => $rDepart->toIso8601String(),
                    'arrival' => $rDepart->copy()->addMinutes($duration + $stops * 90)->toIso8601String(),
                    'duration_minutes' => $duration + $stops * 90,
                    'stops' => $stops,
                ]];
            }

            $perAdult = $base + $i * 9500 + $stops * -20000;
            $legs = $search->return_date ? 2 : 1;
            $cost = ($perAdult * $legs * $search->adults)
                + ($perAdult * $legs * 0.75 * $search->children)
                + ($perAdult * $legs * 0.10 * $search->infants);

            $offers[] = [
                'external_id' => 'FAKE-'.Str::upper(Str::random(10)),
                'itinerary' => $itinerary,
                'airline_code' => $code,
                'airline_name' => $name,
                'cost' => round(max($cost, 30000), 0),
                'currency' => 'XAF',
                'fare_rules' => [
                    'baggage' => $i % 2 === 0 ? '1 bagage 23 kg' : '2 bagages 23 kg',
                    'changes' => $i % 2 === 0 ? 'Modification payante' : 'Modification gratuite',
                    'refund' => $i % 3 === 0 ? 'Non remboursable' : 'Remboursable avec frais',
                ],
                'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 20)),
            ];
        }

        return $offers;
    }

    /** Multi-destinations : un seul dossier, une offre par compagnie couvrant tous les vols. */
    private function searchMulti(FlightSearch $search): array
    {
        $offers = [];

        foreach (self::AIRLINES as $i => [$code, $name]) {
            $itineraryLegs = [];
            $cost = 0.0;
            $skip = false;

            foreach ($search->legs as $n => $leg) {
                $date = \Illuminate\Support\Carbon::parse($leg['date']);
                $seed = crc32($leg['origin'].$leg['destination'].$date->format('Y-m-d'));
                if (($seed + $i) % 6 === 0) {
                    $skip = true; // la compagnie ne dessert pas ce vol
                    break;
                }
                $base = 90000 + ($seed % 400000);
                $depHour = 5 + (($seed >> $i) % 16);
                $duration = 120 + (($seed + $i * 37) % 600);
                $stops = ($seed + $i) % 3 === 0 ? 1 : 0;
                $depart = $date->copy()->setTime($depHour, ($i * 15 + $n * 10) % 60);

                $itineraryLegs[] = [[
                    'from' => $leg['origin'],
                    'to' => $leg['destination'],
                    'flight_number' => $code.(100 + (($seed + $i * 13) % 800)),
                    'departure' => $depart->toIso8601String(),
                    'arrival' => $depart->copy()->addMinutes($duration + $stops * 90)->toIso8601String(),
                    'duration_minutes' => $duration + $stops * 90,
                    'stops' => $stops,
                ]];

                $perAdult = $base + $i * 9500 + $stops * -20000;
                $cost += ($perAdult * $search->adults) + ($perAdult * 0.75 * $search->children) + ($perAdult * 0.10 * $search->infants);
            }

            if ($skip) {
                continue;
            }

            $offers[] = [
                'external_id' => 'FAKE-'.Str::upper(Str::random(10)),
                'itinerary' => ['outbound' => $itineraryLegs[0], 'inbound' => [], 'legs' => $itineraryLegs],
                'airline_code' => $code,
                'airline_name' => $name,
                'cost' => round(max($cost, 30000), 0),
                'currency' => 'XAF',
                'fare_rules' => [
                    'baggage' => $i % 2 === 0 ? '1 bagage 23 kg' : '2 bagages 23 kg',
                    'changes' => $i % 2 === 0 ? 'Modification payante' : 'Modification gratuite',
                    'refund' => $i % 3 === 0 ? 'Non remboursable' : 'Remboursable avec frais',
                ],
                'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 20)),
            ];
        }

        return $offers;
    }

    public function revalidate(FlightOffer $offer): array
    {
        $change = (float) config('flights.fake.price_change_percent', 0);
        $cost = (float) $offer->supplier_cost * (1 + $change / 100);

        return ['available' => true, 'cost' => round($cost, 0), 'currency' => $offer->currency];
    }

    public function createBooking(FlightOrder $order, string $idempotencyKey): array
    {
        if ($existing = $this->findBooking($idempotencyKey)) {
            return $existing; // meme cle = meme resultat, jamais de doublon
        }

        if (config('flights.fake.fail_booking')) {
            throw new SupplierRejectedException('Reservation refusee par le fournisseur (simulation).');
        }

        $booking = [
            'pnr' => Str::upper(Str::random(6)),
            'supplier_order_id' => 'SUP-'.Str::upper(Str::random(8)),
        ];
        Cache::forever($this->key('booking', $idempotencyKey), $booking);

        return $booking;
    }

    public function findBooking(string $idempotencyKey): ?array
    {
        return Cache::get($this->key('booking', $idempotencyKey));
    }

    public function issueTickets(FlightOrder $order, string $idempotencyKey): array
    {
        if ($existing = $this->findTickets($idempotencyKey)) {
            return $existing;
        }

        if (config('flights.fake.fail_ticketing')) {
            throw new SupplierRejectedException('Emission refusee par le fournisseur (simulation).');
        }

        $tickets = [];
        foreach ($order->passengers as $passenger) {
            $tickets[$passenger->id] = '057-'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        }
        Cache::forever($this->key('tickets', $idempotencyKey), $tickets);

        if (config('flights.fake.timeout_after_ticketing')) {
            // Les billets existent chez le fournisseur mais la reponse n'arrive pas.
            throw new SupplierTimeoutException('Delai depasse apres demande d\'emission (simulation).');
        }

        return $tickets;
    }

    public function findTickets(string $idempotencyKey): ?array
    {
        return Cache::get($this->key('tickets', $idempotencyKey));
    }

    public function cancelBooking(FlightOrder $order): bool
    {
        return true;
    }

    private function key(string $type, string $idempotencyKey): string
    {
        return "flights.fake.{$type}.{$idempotencyKey}";
    }
}
