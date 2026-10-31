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
        $quantity = $request->integer('quantity');
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
        ]);

        return response()->json($booking, 201);
    }

    // Le paiement est gere par App\Http\Controllers\Api\PaymentController::pay
    // (multi-passerelles : Peex, pawaPay, MaishaPay, Digitwace).
}
