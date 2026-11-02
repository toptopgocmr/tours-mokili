<?php

namespace App\Services\Flights\Data;

/**
 * Offre normalisee renvoyee par un fournisseur (montants fournisseur, hors
 * marge MOKILI TOUR).
 *
 * slices     : [{origin, destination, departure_at, arrival_at, duration_minutes, stops, segments: [...]}]
 * passengers : [{type: ADT|CHD|INF, count, unit_amount}]
 * fareRules  : {cabin, fare_family, baggage, cabin_baggage, refundable, changeable, change_fee, cancel_fee, conditions}
 */
final class OfferData
{
    public function __construct(
        public readonly string $supplierOfferId,
        public readonly ?string $validatingAirline,
        public readonly ?string $airlineName,
        public readonly array $slices,
        public readonly array $passengers,
        public readonly float $supplierAmount,
        public readonly string $currency,
        public readonly array $fareRules = [],
        public readonly ?string $ticketingDeadline = null,
        public readonly array $raw = [],
    ) {}
}
