<?php

namespace App\Services\Flights;

use App\Services\Flights\Data\OfferData;

/** Prix client = prix fournisseur + marge MOKILI TOUR + frais de service. */
class FlightPricing
{
    public function quote(OfferData $offer): array
    {
        $supplier = round($offer->supplierAmount, 2);
        $markup = round($supplier * (float) config('flights.markup_percent', 0) / 100);
        $payingPax = collect($offer->passengers)->whereIn('type', ['ADT', 'CHD'])->sum('count');
        $fee = round((float) config('flights.service_fee_per_passenger', 0) * $payingPax);

        return [
            'supplier_amount' => $supplier,
            'markup_amount' => $markup,
            'service_fee' => $fee,
            'total_amount' => $supplier + $markup + $fee,
        ];
    }

    /** Lignes par passager affichees au client (marge repartie au prorata). */
    public function passengerLines(OfferData $offer, array $quote): array
    {
        $ratio = $quote['supplier_amount'] > 0 ? ($quote['supplier_amount'] + $quote['markup_amount']) / $quote['supplier_amount'] : 1;

        return array_map(fn ($l) => $l + ['display_amount' => round($l['unit_amount'] * $ratio)], $offer->passengers);
    }
}
