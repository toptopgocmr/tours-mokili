<?php

namespace App\Http\Controllers\Voyage;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Voyage\TravelOffer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Creates a pending Booking for a TravelOffer, then redirects to the
 * shared checkout flow (Peex wallet verification -> payment).
 */
class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, TravelOffer $travelOffer)
    {
        $booking = DB::transaction(function () use ($request, $travelOffer) {
            $quantity = $request->integer('quantity');
            $unitPrice = $travelOffer->discounted_price;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => TravelOffer::class,
                'bookable_id' => $travelOffer->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
                'currency' => $travelOffer->currency,
                'starts_at' => $travelOffer->departure_at,
                'ends_at' => $travelOffer->return_at,
                'status' => 'awaiting_payment',
                'meta' => [
                    'destination' => $travelOffer->destination_city,
                    'type' => $travelOffer->type,
                ],
                'notes' => $request->string('notes'),
            ]);
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Reservation creee. Verifiez votre portefeuille pour payer.');
    }
}
