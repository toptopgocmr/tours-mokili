<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Shared by the 5 non-Voyage module controllers (Logement, Voiture,
 * Divertissement, Marketplace, Fret) so each one's book()/order() action
 * can create a polymorphic Booking (see App\Models\Booking) without
 * duplicating the same Booking::create(...) plumbing that
 * BookingController::store already does for Voyage\TravelOffer.
 */
trait BooksListings
{
    protected function createBooking(
        Request $request,
        object $bookable,
        string $bookableClass,
        float $unitPrice,
        string $currency,
        mixed $startsAt = null,
        mixed $endsAt = null,
    ): Booking {
        $quantity = max(1, (int) $request->integer('quantity', 1));

        return Booking::create([
            'user_id' => $request->user()->id,
            'bookable_type' => $bookableClass,
            'bookable_id' => $bookable->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'currency' => $currency,
            'starts_at' => $request->filled('starts_at') ? $request->input('starts_at') : $startsAt,
            'ends_at' => $request->filled('ends_at') ? $request->input('ends_at') : $endsAt,
            'status' => 'awaiting_payment',
            'notes' => $request->filled('notes') ? $request->input('notes') : null,
        ]);
    }
}
