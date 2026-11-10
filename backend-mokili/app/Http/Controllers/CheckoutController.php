<?php

namespace App\Http\Controllers;

use App\Support\PaymentMessage;

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
            'methods' => $this->manager->catalogue('collect', true),
            'lastPayment' => tap($booking->payments()->latest()->first(), function ($p) {
                if ($p) {
                    $p->failure_reason = PaymentMessage::clean($p->failure_reason);
                }
            }),
            'receiptUrl' => $this->isPaid($booking) ? route('checkout.receipt', $booking) : null,
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
            // Billetterie : prix modifie / offre expiree -> le dossier explique quoi faire.
            $flight = $booking->bookable;
            if ($flight instanceof \App\Models\Flights\FlightBooking && ! in_array($flight->fresh()->status, ['PRICE_CONFIRMED', 'PAYMENT_PENDING'], true)) {
                return redirect()->route('voyage.vols.dossier', $flight->reference)->with('error', PaymentMessage::clean($e->getMessage()));
            }

            return back()->with('error', 'Paiement impossible : '.PaymentMessage::clean($e->getMessage()));
        }

        return match ($payment->status) {
            'paid' => redirect()->route('checkout.show', $booking)->with('success', 'Paiement recu. Reservation confirmee !'),
            'failed' => back()->with('error', 'Paiement refuse : '.(PaymentMessage::clean($payment->failure_reason) ?? 'reessayez.')),
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
            'payment' => $payment ? ['id' => $payment->id, 'status' => $payment->status, 'failure_reason' => PaymentMessage::clean($payment->failure_reason), 'paid_at' => $payment->paid_at] : null,
        ]);
    }

    /** Recu de paiement imprimable / telechargeable en PDF (client ou equipe MOKILI TOUR). */
    public function receipt(Booking $booking): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        abort_unless($booking->user_id === $user->id || in_array($user->role, ['admin', 'agent'], true), 403);
        abort_unless($this->isPaid($booking), 404);

        $payment = $booking->payments()->where('status', 'paid')->latest('paid_at')->first();
        $b = $booking->bookable;

        return view('receipts.booking', [
            'booking' => $booking,
            'payment' => $payment,
            'client' => $booking->user,
            'title' => $b->title ?? $b->name ?? ($booking->meta['park'] ?? 'Reservation MOKILI TOUR'),
            'place' => collect([$b->city ?? ($booking->meta['city'] ?? null), $b->country ?? null])->filter()->implode(', '),
            'kind' => class_basename($booking->bookable_type),
            'network' => $payment?->network ? (config('payments.networks.'.$payment->country_code.'.'.$payment->network.'.label') ?? $payment->network) : 'Mobile Money',
            // Numero masque : +242 06 ••• ••19
            'phone' => $payment?->phone ? (($dial = config('payments.dial_codes.'.$payment->country_code)) && str_starts_with($payment->phone, $dial)
                ? $dial.' '.substr($payment->phone, strlen($dial), 2).' ••• ••'.substr($payment->phone, -2)
                : '••• ••'.substr($payment->phone, -2)) : null,
        ]);
    }

    protected function isPaid(Booking $booking): bool
    {
        return in_array($booking->status, ['confirmed', 'paid', 'completed'], true)
            || $booking->payments()->where('status', 'paid')->exists();
    }
}
