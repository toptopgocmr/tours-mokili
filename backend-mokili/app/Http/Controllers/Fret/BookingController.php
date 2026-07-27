<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Requests transport on a published FreightOffer (weight -> price per kg),
 * then redirects to the shared checkout flow. A companion Shipment is
 * created immediately so the customer gets a tracking code right away,
 * mirroring DHL's "get a quote -> create shipment -> track" pattern
 * rather than waiting for payment confirmation to generate one.
 */
class BookingController extends Controller
{
    public function store(Request $request, FreightOffer $freightOffer): RedirectResponse
    {
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:'.max(0.1, (float) ($freightOffer->capacity_kg ?: 100000))],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        [$booking, $shipment] = DB::transaction(function () use ($data, $freightOffer) {
            $unitPrice = $freightOffer->price_per_kg;
            $total = round($unitPrice * $data['weight_kg'], 2);

            $shipment = Shipment::create([
                'user_id' => Auth::id(),
                'origin_city' => $freightOffer->origin_city,
                'origin_country' => $freightOffer->origin_country,
                'destination_city' => $freightOffer->destination_city,
                'destination_country' => $freightOffer->destination_country,
                'weight_kg' => $data['weight_kg'],
                'dimensions' => $data['dimensions'] ?? null,
                'mode' => $freightOffer->mode,
                'status' => 'enregistre',
                'estimated_price' => $total,
                'currency' => $freightOffer->currency,
            ]);

            $booking = Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => FreightOffer::class,
                'bookable_id' => $freightOffer->id,
                'quantity' => $data['weight_kg'],
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'currency' => $freightOffer->currency,
                'status' => 'awaiting_payment',
                'meta' => [
                    'origin' => $freightOffer->origin_city,
                    'destination' => $freightOffer->destination_city,
                    'tracking_code' => $shipment->tracking_code,
                    'shipment_id' => $shipment->id,
                ],
                'notes' => $data['notes'] ?? null,
            ]);

            return [$booking, $shipment];
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', "Demande enregistree. Votre code de suivi est {$shipment->tracking_code}.");
    }
}
