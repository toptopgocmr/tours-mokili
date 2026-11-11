<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\Airport;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightFare;
use App\Models\Flights\FlightOffer;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Carbon\CarbonImmutable;

/**
 * Fournisseur "manuel" : tarifs negocies avec un consolidateur, saisis dans
 * le back-office (/admin/vols/tarifs). La recherche et la revalidation sont
 * automatiques ; la reservation (PNR) et l'emission sont realisees par
 * l'equipe billetterie chez le consolidateur puis saisies dans le dossier.
 * Le client n'est jamais informe d'un billet emis avant le statut TICKETED.
 */
class ManualFareSupplier extends AbstractSupplier
{
    public function search(SearchCriteria $c): array
    {
        $legs = $c->itinerary();

        // Tarifs disponibles pour chaque trajet (aller, retour ou chaque vol multi-destinations).
        $candidates = [];
        foreach ($legs as $leg) {
            $fares = $this->faresFor($leg['origin'], $leg['destination'], $leg['date'], $c);
            if ($fares->isEmpty()) {
                return [];
            }
            $candidates[] = $fares->all();
        }

        // Combinaisons des tarifs, plafonnees pour ne pas exploser en multi-destinations.
        $max = (int) config('flights.max_offers', 60);
        $combos = [[]];
        foreach ($candidates as $fares) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($fares as $fare) {
                    $next[] = array_merge($combo, [$fare]);
                    if (count($next) >= $max * 10) {
                        break 2;
                    }
                }
            }
            $combos = $next;
        }

        // Aller-retour / multi-destinations : on propose d'abord les voyages
        // sur une seule compagnie (billet unique), les melanges en dernier recours.
        $sameAirline = array_values(array_filter(
            $combos,
            fn (array $fares) => count(array_unique(array_map(fn ($f) => $f->airline_code, $fares))) === 1,
        ));
        $combos = array_slice($sameAirline ?: $combos, 0, $max);

        return array_map(fn (array $fares) => $this->buildOffer($c, $fares), $combos);
    }

    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        $ids = $this->parseOfferId($offer->supplier_offer_id);
        $legs = $c->itinerary();

        if (count($ids) !== count($legs)) {
            return null;
        }

        $fares = [];
        foreach ($ids as $i => $id) {
            $fare = FlightFare::find($id);
            if (! $fare || ! $this->isSellable($fare, $legs[$i]['date'], $c)) {
                return null;
            }
            $fares[] = $fare;
        }

        return $this->buildOffer($c, $fares);
    }

    public function book(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Reservation a effectuer par l'equipe billetterie chez le consolidateur.");
    }

    public function issue(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Emission a effectuer par l'equipe billetterie.");
    }

    public function retrieve(FlightBooking $booking): SupplierResult
    {
        // Pas d'API : l'etat de reference est celui saisi par l'equipe.
        $tickets = $booking->tickets()->pluck('ticket_number', 'flight_passenger_id')->all();

        return new SupplierResult(
            $booking->pnr ? SupplierResult::SUCCESS : SupplierResult::PENDING,
            $booking->pnr,
            $booking->supplier_booking_ref,
            $tickets,
            null,
            [],
            $booking->pnr ? 'booked' : 'pending',
            $tickets ? 'issued' : ($booking->pnr ? 'pending' : 'none'),
        );
    }

    public function cancel(FlightBooking $booking): SupplierResult
    {
        return SupplierResult::pending("Annulation a effectuer par l'equipe billetterie chez le consolidateur.");
    }

    protected function faresFor(string $from, string $to, CarbonImmutable $date, SearchCriteria $c)
    {
        return FlightFare::query()
            ->where('flight_supplier_id', $this->supplier?->id)
            ->where('origin', $from)
            ->where('destination', $to)
            ->where('cabin', $c->cabin)
            ->where('is_active', true)
            ->get()
            ->filter(fn (FlightFare $f) => $this->isSellable($f, $date, $c))
            ->values();
    }

    protected function isSellable(FlightFare $f, CarbonImmutable $date, SearchCriteria $c): bool
    {
        if (! $f->operatesOn($date)) {
            return false;
        }
        if ($f->seats_available !== null && $f->seats_available < $c->seatCount()) {
            return false;
        }
        // On ne vend pas un vol qui part avant le delai d'emission.
        $departure = CarbonImmutable::parse($date->toDateString().' '.$f->departure_time, $this->timezone($f->origin));

        return $departure->gt(now()->addHours(max(2, (int) $f->ticketing_delay_hours)));
    }

    /** @param FlightFare[] $fares un tarif par trajet, dans l'ordre du voyage */
    protected function buildOffer(SearchCriteria $c, array $fares): OfferData
    {
        $legs = $c->itinerary();
        $first = $fares[0];
        $adult = $child = $infant = 0.0;
        $slices = [];
        foreach ($fares as $i => $f) {
            $base = (float) $f->base_fare;
            $tax = (float) $f->taxes;
            $adult += $base + $tax;
            $child += round($base * $f->child_percent / 100) + $tax;
            $infant += round(($base + $tax) * $f->infant_percent / 100);
            $slices[] = $this->slice($f, $legs[$i]['date']);
        }

        $lines = $this->passengerLines($c, $adult, $child, $infant);
        $fees = fn (string $key) => $first->{$key} === null
            ? null
            : (float) collect($fares)->sum(fn ($f) => (float) ($f->{$key} ?? 0));

        return new OfferData(
            supplierOfferId: 'fare:'.implode(':', array_map(fn ($f) => $f->id, $fares)),
            validatingAirline: $first->airline_code,
            airlineName: $first->airline_name,
            slices: $slices,
            passengers: $lines,
            supplierAmount: $this->linesTotal($lines),
            currency: $first->currency,
            fareRules: [
                'cabin' => $first->cabin,
                'fare_family' => $first->fare_family,
                'baggage' => $first->baggage,
                'cabin_baggage' => $first->cabin_baggage,
                'refundable' => collect($fares)->every(fn ($f) => (bool) $f->refundable),
                'changeable' => collect($fares)->every(fn ($f) => (bool) $f->changeable),
                'change_fee' => $fees('change_fee'),
                'cancel_fee' => $fees('cancel_fee'),
                'conditions' => trim(implode("\n", array_filter(array_map(fn ($f) => $f->conditions, $fares)))) ?: null,
            ],
            ticketingDeadline: now()->addHours((int) $first->ticketing_delay_hours)->toIso8601String(),
            raw: ['fares' => array_map(fn ($f) => $f->id, $fares)],
        );
    }

    protected function slice(FlightFare $f, CarbonImmutable $date): array
    {
        $dep = $date->toDateString().'T'.$f->departure_time;
        $arr = $date->addDays((int) $f->arrival_day_offset)->toDateString().'T'.$f->arrival_time;

        return [
            'origin' => $f->origin,
            'destination' => $f->destination,
            'departure_at' => $dep,
            'arrival_at' => $arr,
            'duration_minutes' => $f->duration_minutes,
            'stops' => (int) $f->stops,
            'via' => $f->via,
            'segments' => [[
                'airline_code' => $f->airline_code,
                'airline_name' => $f->airline_name,
                'flight_number' => $f->flight_number,
                'origin' => $f->origin,
                'destination' => $f->destination,
                'departure_at' => $dep,
                'arrival_at' => $arr,
                'cabin' => $f->cabin,
            ]],
        ];
    }

    /** "fare:12" / "fare:12:34" / "fare:12:34:56..." -> [12, 34, 56] */
    protected function parseOfferId(?string $id): array
    {
        $parts = explode(':', (string) $id);
        array_shift($parts);

        return array_values(array_filter(array_map('intval', $parts)));
    }

    protected function timezone(string $iata): string
    {
        static $cache = [];

        return $cache[$iata] ??= (Airport::lookup($iata)?->timezone ?: config('app.timezone', 'UTC'));
    }
}
