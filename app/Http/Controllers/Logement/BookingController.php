<?php

namespace App\Http\Controllers\Logement;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Logement\LodgingListing;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Creates a pending Booking for a LodgingListing (check-in/check-out
 * dates -> nights -> total), then redirects to the shared checkout
 * flow (Peex wallet verification -> payment) - same pattern as
 * Voyage\BookingController.
 */
class BookingController extends Controller
{
    public function store(Request $request, LodgingListing $lodgingListing): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'guests' => ['required', 'integer', 'min:1', 'max:'.max(1, $lodgingListing->max_guests)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = DB::transaction(function () use ($data, $lodgingListing) {
            $nights = max(1, Carbon::parse($data['starts_at'])->diffInDays(Carbon::parse($data['ends_at'])));
            $unitPrice = $lodgingListing->price_per_night;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => LodgingListing::class,
                'bookable_id' => $lodgingListing->id,
                'quantity' => $nights,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $nights,
                'currency' => $lodgingListing->currency,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => 'awaiting_payment',
                'meta' => [
                    'city' => $lodgingListing->city,
                    'guests' => $data['guests'],
                ],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Reservation creee. Verifiez votre portefeuille pour payer.');
    }
}
