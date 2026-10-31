<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayBookingRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paiement Mobile Money multi-passerelles pour l'app Flutter (et le
 * checkout web en AJAX) : Peex, pawaPay, MaishaPay, Digitwace.
 *
 *   GET  /api/payments/methods           -> pays, operateurs, passerelles dispo
 *   POST /api/bookings/{booking}/pay     -> lance la collecte
 *   GET  /api/payments/{payment}         -> statut (re-verifie si en attente)
 */
class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentGatewayManager $manager,
    ) {}

    public function methods(Request $request): JsonResponse
    {
        return response()->json($this->manager->catalogue($request->input('type') === 'payout' ? 'payout' : 'collect'));
    }

    public function pay(PayBookingRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

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
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $this->messageFor($payment),
            'payment' => $this->present($payment),
            'booking' => $booking->fresh(),
        ], $payment->status === 'failed' ? 422 : 200);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $recheckAfter = (int) config('payments.pending_recheck_after', 20);

        if (in_array($payment->status, ['pending', 'processing'], true)
            && (! $payment->last_checked_at || $payment->last_checked_at->diffInSeconds(now()) >= $recheckAfter)) {
            $payment = $this->payments->refreshPayment($payment);
        }

        return response()->json([
            'message' => $this->messageFor($payment),
            'payment' => $this->present($payment),
            'booking' => $payment->booking,
        ]);
    }

    protected function present(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'gateway' => $payment->provider,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'phone' => $payment->phone,
            'network' => $payment->network,
            'failure_reason' => $payment->failure_reason,
            'paid_at' => $payment->paid_at,
        ];
    }

    protected function messageFor(Payment $payment): string
    {
        return match ($payment->status) {
            'paid' => 'Paiement recu. Votre reservation est confirmee !',
            'failed' => $payment->failure_reason ?: 'Le paiement a echoue.',
            default => 'Validez le paiement sur votre telephone (code secret Mobile Money).',
        };
    }
}
