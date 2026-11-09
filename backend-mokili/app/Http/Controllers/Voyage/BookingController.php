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
        // Les billets d'avion passent par la billetterie (passagers, prix
        // revalide, PNR, emission) : pas de reservation "panier" pour un vol.
        if ($travelOffer->type === 'vol') {
            return redirect('/voyage?type=vol')->with('error', 'Recherchez votre vol pour reserver votre billet.');
        }

        $quantity = $request->integer('quantity');
        if ($travelOffer->type === 'circuit') {
            if ($travelOffer->departure_at && $travelOffer->departure_at->isPast()) {
                return back()->with('error', 'Ce trajet est deja parti.');
            }
            if ($quantity > (int) $travelOffer->seats_available) {
                return back()->withErrors(['quantity' => $travelOffer->seats_available > 0
                    ? 'Il ne reste que '.$travelOffer->seats_available.' place(s) sur ce trajet.'
                    : 'Ce trajet est complet.']);
            }
        }

        $booking = DB::transaction(function () use ($request, $travelOffer, $quantity) {
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
