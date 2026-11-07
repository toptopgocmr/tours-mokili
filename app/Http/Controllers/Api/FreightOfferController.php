<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use App\Services\Fret\FreightOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

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
            ->when($request->filled('service_type'), fn ($q) => $q->where('service_type', $request->string('service_type')))
            ->with('partnerProfile:user_id,company_name,trade_name')
            ->latest()
            ->paginate(10);

        return response()->json($offers);
    }

    public function show(FreightOffer $freightOffer): JsonResponse
    {
        abort_unless(FreightOffer::active()->whereKey($freightOffer->id)->exists(), 404);

        return response()->json($freightOffer->load('partnerProfile:user_id,company_name,trade_name'));
    }

    public function book(Request $request, FreightOffer $freightOffer, FreightOrderService $orders): JsonResponse
    {
        $data = $request->validate([
            // L'application envoie le poids (kg) dans "quantity" comme les autres modules.
            'quantity' => ['required', 'numeric', 'min:0.1', 'max:10000000'],
            'volume_m3' => ['nullable', 'numeric', 'min:0.001', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $order = $orders->createInstant($request->user(), $freightOffer, [
                'weight_kg' => (float) $data['quantity'],
                'volume_m3' => isset($data['volume_m3']) ? (float) $data['volume_m3'] : null,
            ], $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Reponse compatible : "id" = reservation de paiement (booking) comme avant.
        return response()->json($order->booking->toArray() + ['order_reference' => $order->reference, 'tracking_code' => $order->shipment->tracking_code], 201);
    }
}
