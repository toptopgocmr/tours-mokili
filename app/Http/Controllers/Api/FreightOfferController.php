<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for the Flutter app - FRET module: catalogue of published
 * freight offers (follows the Voyage pilot module pattern, see
 * TravelOfferController). Booking a weight on an offer also creates a
 * companion Shipment immediately, mirroring the web
 * Fret\BookingController so the customer gets a tracking code right away.
 */
class FreightOfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $offers = FreightOffer::query()
            ->active()
            ->when($request->filled('origin'), fn ($q) => $q->where(
                'origin_city', 'like', '%'.$request->string('origin').'%'
            ))
            ->when($request->filled('destination'), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->string('mode')))
            ->latest()
            ->paginate(10);

        return response()->json($offers);
    }

    public function show(FreightOffer $freightOffer): JsonResponse
    {
        abort_unless($freightOffer->is_active && $freightOffer->status === 'published', 404);

        return response()->json($freightOffer);
    }

    public function book(Request $request, FreightOffer $freightOffer): JsonResponse
    {
        $data = $request->validate([
            // The mobile app sends the weight (kg) as "quantity" to stay
            // consistent with every other module's book() payload.
            'quantity' => ['required', 'numeric', 'min:0.1', 'max:'.max(0.1, (float) ($freightOffer->capacity_kg ?: 100000))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = DB::transaction(function () use ($data, $freightOffer) {
            $weightKg = $data['quantity'];
            $unitPrice = $freightOffer->price_per_kg;
            $total = round($unitPrice * $weightKg, 2);

            $shipment = Shipment::create([
                'user_id' => Auth::id(),
                'origin_city' => $freightOffer->origin_city,
                'origin_country' => $freightOffer->origin_country,
                'destination_city' => $freightOffer->destination_city,
                'destination_country' => $freightOffer->destination_country,
                'weight_kg' => $weightKg,
                'mode' => $freightOffer->mode,
                'status' => 'enregistre',
                'estimated_price' => $total,
                'currency' => $freightOffer->currency,
            ]);

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => FreightOffer::class,
                'bookable_id' => $freightOffer->id,
                'quantity' => $weightKg,
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
        });

        return response()->json($booking, 201);
    }
}
