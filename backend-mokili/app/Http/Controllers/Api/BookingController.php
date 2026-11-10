<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->bookings()->with('bookable')->latest()->paginate(10)
        );
    }

    public function store(StoreBookingRequest $request, TravelOffer $travelOffer): JsonResponse
    {
        if ($travelOffer->type === 'vol') {
            return response()->json(['message' => 'Les billets d\'avion se reservent via /api/flights/search.', 'reason' => 'use_flights'], 422);
        }

        $quantity = $request->integer('quantity');
        // Trajets (train, bus, canot, taxi) : memes regles que le site.
        if ($travelOffer->type === 'circuit') {
            if ($travelOffer->departure_at && $travelOffer->departure_at->isPast()) {
                return response()->json(['message' => 'Ce trajet est deja parti.'], 422);
            }
            if ($quantity > (int) $travelOffer->seats_available) {
                return response()->json(['message' => $travelOffer->seats_available > 0
                    ? 'Il ne reste que '.$travelOffer->seats_available.' place(s) sur ce trajet.'
                    : 'Ce trajet est complet.', 'errors' => ['quantity' => ['Places insuffisantes.']]], 422);
            }
        }
        $unitPrice = $travelOffer->discounted_price;

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'bookable_type' => TravelOffer::class,
            'bookable_id' => $travelOffer->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'currency' => $travelOffer->currency,
            'starts_at' => $travelOffer->departure_at,
            'ends_at' => $travelOffer->return_at,
            'status' => 'awaiting_payment',
            'meta' => ['destination' => $travelOffer->destination_city, 'type' => $travelOffer->type],
            'notes' => $request->string('notes'),
        ]);

        return response()->json($booking, 201);
    }

    // Le paiement est gere par App\Http\Controllers\Api\PaymentController::pay
    // (multi-passerelles : Peex, pawaPay, MaishaPay, Digitwace).
}
