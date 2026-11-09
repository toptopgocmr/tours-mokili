<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightIdempotencyKey;
use App\Models\Flights\FlightOffer;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Fournisseur de TEST : offres synthetiques sur n'importe quelle route,
 * PNR et numeros de billets fictifs. Active seulement avec FLIGHTS_SANDBOX=true.
 *
 * config('flights.sandbox_behaviour') permet de simuler les cas de recette :
 * book_fail, book_timeout, issue_fail, issue_timeout, price_change, unavailable.
 */
class SandboxSupplier extends AbstractSupplier
{
    public function search(SearchCriteria $c): array
    {
        return [
            $this->offer($c, 'SBX1', 'MK', 'Mokili Air (test)', 0, 180000),
            $this->offer($c, 'SBX2', 'SB', 'Sandbox Airways (test)', 1, 145000),
        ];
    }

    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        $behaviour = config('flights.sandbox_behaviour');
        if ($behaviour === 'unavailable') {
            return null;
        }

        $code = explode(':', (string) $offer->supplier_offer_id)[1] ?? 'SBX1';
        $base = $code === 'SBX2' ? 145000 : 180000;
        if ($behaviour === 'price_change') {
            $base += 25000;
        }

        return $this->offer($c, $code, $offer->validating_airline, $offer->airline_name, $code === 'SBX2' ? 1 : 0, $base);
    }

    public function book(FlightBooking $booking, string $key): SupplierResult
    {
        return match (config('flights.sandbox_behaviour')) {
            'book_fail' => SupplierResult::failed('Classe tarifaire complete (sandbox).'),
            'book_timeout' => SupplierResult::unknown('Timeout fournisseur (sandbox).'),
            default => SupplierResult::success(strtoupper(Str::random(6)), 'SBX-'.$booking->id),
        };
    }

    public function issue(FlightBooking $booking, string $key): SupplierResult
    {
        return match (config('flights.sandbox_behaviour')) {
            'issue_fail' => SupplierResult::failed('Emission refusee (sandbox).'),
            'issue_timeout' => SupplierResult::unknown('Timeout emission (sandbox).'),
            default => SupplierResult::success($booking->pnr, $booking->supplier_booking_ref, $this->ticketNumbers($booking)),
        };
    }

    public function retrieve(FlightBooking $booking): SupplierResult
    {
        // Simule l'etat reel : une demande "timeout" a quand meme abouti
        // si config('flights.sandbox_timeout_succeeded') est vrai.
        $succeeded = (bool) config('flights.sandbox_timeout_succeeded', false);
        $bookKey = FlightIdempotencyKey::where('key', "book:{$booking->id}")->first();
        $issueKey = FlightIdempotencyKey::where('key', "issue:{$booking->id}")->first();

        $pnr = $booking->pnr ?? ($bookKey && $succeeded ? 'SBXPNR' : null);
        $issued = $booking->tickets()->exists() || ($issueKey && $succeeded);

        return new SupplierResult(
            $pnr ? SupplierResult::SUCCESS : SupplierResult::PENDING,
            $pnr,
            $pnr ? 'SBX-'.$booking->id : null,
            $issued ? ($booking->tickets()->pluck('ticket_number', 'flight_passenger_id')->all() ?: $this->ticketNumbers($booking)) : [],
            null,
            [],
            $pnr ? 'booked' : ($bookKey ? 'none' : 'none'),
            $issued ? 'issued' : 'none',
        );
    }

    public function cancel(FlightBooking $booking): SupplierResult
    {
        return SupplierResult::success($booking->pnr, $booking->supplier_booking_ref);
    }

    protected function ticketNumbers(FlightBooking $booking): array
    {
        return $booking->passengers()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => '999'.str_pad((string) ($booking->id * 100 + $id), 10, '0', STR_PAD_LEFT)])
            ->all();
    }

    protected function offer(SearchCriteria $c, string $code, ?string $airline, ?string $name, int $stops, float $base): OfferData
    {
        $slices = array_map(
            fn ($leg) => $this->slice($leg['origin'], $leg['destination'], $leg['date'], $airline, $name, $stops),
            $c->itinerary(),
        );
        $mult = count($slices);
        $lines = $this->passengerLines($c, $base * $mult, $base * 0.75 * $mult, $base * 0.1 * $mult);

        return new OfferData(
            supplierOfferId: 'sandbox:'.$code,
            validatingAirline: $airline,
            airlineName: $name,
            slices: $slices,
            passengers: $lines,
            supplierAmount: $this->linesTotal($lines),
            currency: 'XAF',
            fareRules: ['cabin' => $c->cabin, 'baggage' => '1 x 23 kg', 'cabin_baggage' => '1 x 8 kg', 'refundable' => true, 'changeable' => true, 'change_fee' => 15000, 'cancel_fee' => 30000],
            ticketingDeadline: now()->addDay()->toIso8601String(),
        );
    }

    protected function slice(string $from, string $to, CarbonImmutable $date, ?string $airline, ?string $name, int $stops): array
    {
        $dep = $date->toDateString().'T08:30';
        $arr = $date->toDateString().($stops ? 'T17:45' : 'T12:10');

        return [
            'origin' => $from, 'destination' => $to, 'departure_at' => $dep, 'arrival_at' => $arr,
            'duration_minutes' => $stops ? 555 : 220, 'stops' => $stops, 'via' => $stops ? 'ADD' : null,
            'segments' => [['airline_code' => $airline, 'airline_name' => $name, 'flight_number' => $airline.'100', 'origin' => $from, 'destination' => $to, 'departure_at' => $dep, 'arrival_at' => $arr]],
        ];
    }
}
