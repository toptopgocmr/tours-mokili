<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayBookingRequest;
use App\Models\Booking;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Checkout web commun a tous les modules (reservations polymorphes).
 *
 * 1. /checkout/{booking}            : recapitulatif + choix pays / operateur / passerelle
 * 2. POST /checkout/{booking}/payer : demande Mobile Money (Peex, pawaPay, MaishaPay, Digitwace)
 * 3. GET /checkout/{booking}/statut : la page interroge le statut jusqu'a confirmation
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentGatewayManager $manager,
    ) {}

    public function show(Booking $booking): Response
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        return Inertia::render('Checkout', [
            'booking' => $booking->load('bookable'),
            'wallet' => Auth::user()->wallet,
            'methods' => $this->manager->catalogue('collect'),
            'lastPayment' => $booking->payments()->latest()->first(),
        ]);
    }

    public function pay(PayBookingRequest $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        try {
            $payment = $this->payments->collect(
                $booking,
                $request->user(),
                (string) $request->input('gateway'),
                (string) $request->input('phone'),
                (string) $request->input('country_code'),
                $request->input('network'),
            );
        } catch (PaymentGatewayException $e) {
            // Billetterie : le dossier explique quoi faire (prix modifie, offre expiree...).
            $flight = $booking->bookable;
            if ($flight instanceof \App\Models\Flights\FlightOrder) {
                return redirect()->route('vols.show', $flight->reference)->with('error', $e->getMessage());
            }

            return back()->with('error', 'Paiement impossible : '.$e->getMessage());
        }

        return match ($payment->status) {
            'paid' => redirect()->route('checkout.show', $booking)->with('success', 'Paiement recu. Reservation confirmee !'),
            'failed' => back()->with('error', 'Paiement refuse : '.($payment->failure_reason ?? 'reessayez.')),
            default => redirect()->route('checkout.show', $booking)
                ->with('success', 'Demande envoyee sur votre telephone : validez le paiement avec votre code secret Mobile Money.'),
        };
    }

    public function status(Booking $booking): JsonResponse
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        $payment = $booking->payments()->latest()->first();

        if ($payment && in_array($payment->status, ['pending', 'processing'], true)
            && (! $payment->last_checked_at || $payment->last_checked_at->diffInSeconds(now()) >= (int) config('payments.pending_recheck_after', 20))) {
            $payment = $this->payments->refreshPayment($payment);
        }

        return response()->json([
            'booking_status' => $booking->fresh()->status,
            'payment' => $payment?->only(['id', 'status', 'provider', 'failure_reason', 'paid_at']),
        ]);
    }
}
