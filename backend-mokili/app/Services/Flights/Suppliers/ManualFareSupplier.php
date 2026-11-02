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
        $outbound = $this->faresFor($c->origin, $c->destination, $c->departDate, $c);
        if ($outbound->isEmpty()) {
            return [];
        }

        if (! $c->isRoundTrip()) {
            return $outbound->map(fn (FlightFare $f) => $this->buildOffer($c, $f))->all();
        }

        $inbound = $this->faresFor($c->destination, $c->origin, $c->returnDate, $c);
        $offers = [];
        foreach ($outbound as $out) {
            foreach ($inbound as $in) {
                $offers[] = $this->buildOffer($c, $out, $in);
            }
        }

        return $offers;
    }

    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        [$outId, $inId] = $this->parseOfferId($offer->supplier_offer_id);

        $out = FlightFare::find($outId);
        $in = $inId ? FlightFare::find($inId) : null;

        if (! $out || ! $this->isSellable($out, $c->departDate, $c)) {
            return null;
        }
        if ($inId && (! $in || ! $c->returnDate || ! $this->isSellable($in, $c->returnDate, $c))) {
            return null;
        }

        return $this->buildOffer($c, $out, $in);
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

    protected function buildOffer(SearchCriteria $c, FlightFare $out, ?FlightFare $in = null): OfferData
    {
        $fares = array_filter([$out, $in]);
        $adult = $child = $infant = 0.0;
        foreach ($fares as $f) {
            $base = (float) $f->base_fare;
            $tax = (float) $f->taxes;
            $adult += $base + $tax;
            $child += round($base * $f->child_percent / 100) + $tax;
            $infant += round(($base + $tax) * $f->infant_percent / 100);
        }

        $lines = $this->passengerLines($c, $adult, $child, $infant);
        $slices = [$this->slice($out, $c->departDate)];
        if ($in) {
            $slices[] = $this->slice($in, $c->returnDate);
        }

        $id = 'fare:'.$out->id.($in ? ':'.$in->id : '');

        return new OfferData(
            supplierOfferId: $id,
            validatingAirline: $out->airline_code,
            airlineName: $out->airline_name,
            slices: $slices,
            passengers: $lines,
            supplierAmount: $this->linesTotal($lines),
            currency: $out->currency,
            fareRules: [
                'cabin' => $out->cabin,
                'fare_family' => $out->fare_family,
                'baggage' => $out->baggage,
                'cabin_baggage' => $out->cabin_baggage,
                'refundable' => $out->refundable && (! $in || $in->refundable),
                'changeable' => $out->changeable && (! $in || $in->changeable),
                'change_fee' => $out->change_fee !== null ? (float) $out->change_fee + (float) ($in?->change_fee ?? 0) : null,
                'cancel_fee' => $out->cancel_fee !== null ? (float) $out->cancel_fee + (float) ($in?->cancel_fee ?? 0) : null,
                'conditions' => trim(($out->conditions ?? '')."\n".($in?->conditions ?? '')) ?: null,
            ],
            ticketingDeadline: now()->addHours((int) $out->ticketing_delay_hours)->toIso8601String(),
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

    protected function parseOfferId(?string $id): array
    {
        $parts = explode(':', (string) $id);

        return [(int) ($parts[1] ?? 0), isset($parts[2]) ? (int) $parts[2] : null];
    }

    protected function timezone(string $iata): string
    {
        static $cache = [];

        return $cache[$iata] ??= (Airport::lookup($iata)?->timezone ?: config('app.timezone', 'UTC'));
    }
}
