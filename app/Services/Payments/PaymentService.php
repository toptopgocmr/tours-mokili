<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Flights\FlightPaymentBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestration des paiements, commune au site web, a l'app mobile, au
 * back-office et aux webhooks :
 *   - collect()       : cree le Payment et envoie la demande a la passerelle
 *   - payout()        : cree le Payout et envoie l'argent
 *   - refresh*()      : re-interroge la passerelle pour un statut en attente
 *   - handleWebhook() : applique le callback d'un agregateur
 * La reservation passe a "confirmed" uniquement quand l'argent est recu.
 */
class PaymentService
{
    public function __construct(protected PaymentGatewayManager $manager) {}

    public function collect(Booking $booking, User $user, string $gateway, string $phone, string $country, ?string $network = null): Payment
    {
        $country = strtoupper($country);
        $gateway = strtolower($gateway);

        if (! $this->manager->isAvailable($gateway, 'collect', $country)) {
            throw new PaymentGatewayException('Ce moyen de paiement n\'est pas disponible pour ce pays.');
        }

        if (in_array($booking->status, ['confirmed', 'completed', 'refunded'], true)) {
            throw new PaymentGatewayException('Cette reservation est deja payee.');
        }

        $msisdn = PaymentGatewayManager::normalizePhone($phone, $country);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'provider' => $gateway,
            'method' => 'mobile_money',
            'phone' => $msisdn,
            'country_code' => $country,
            'network' => $network ? strtoupper($network) : null,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'status' => 'pending',
        ]);

        $request = new PaymentRequest(
            reference: $payment->reference,
            amount: (float) $booking->total_amount,
            currency: $booking->currency,
            phone: $msisdn,
            country: $country,
            network: $payment->network,
            customerName: $user->name,
            customerEmail: $user->email,
            description: 'MOKILI TOUR '.$booking->reference,
            callbackUrl: $this->webhookUrl($gateway),
        );

        try {
            $result = $this->manager->gateway($gateway)->collect($request);
        } catch (Throwable $e) {
            $payment->update(['status' => 'failed', 'failure_reason' => mb_substr($e->getMessage(), 0, 250)]);
            report($e);

            throw $e instanceof PaymentGatewayException ? $e : new PaymentGatewayException('Le service de paiement est indisponible, reessayez.');
        }

        FlightPaymentBridge::initiated($payment); // dossier de vol : PRICE_CONFIRMED -> PAYMENT_PENDING

        $this->applyToPayment($payment, $result);

        return $payment->fresh();
    }

    public function payout(array $data, ?User $admin = null): Payout
    {
        $gateway = strtolower($data['gateway']);
        $country = strtoupper($data['country_code']);

        if (! $this->manager->isAvailable($gateway, 'payout', $country)) {
            throw new PaymentGatewayException('Ce moyen de decaissement n\'est pas disponible pour ce pays.');
        }

        $msisdn = PaymentGatewayManager::normalizePhone($data['phone'], $country);

        $payout = Payout::create([
            'gateway' => $gateway,
            'user_id' => $data['user_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'created_by' => $admin?->id,
            'beneficiary_name' => $data['beneficiary_name'],
            'phone' => $msisdn,
            'country_code' => $country,
            'network' => isset($data['network']) ? strtoupper($data['network']) : null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'XAF',
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        $request = new PaymentRequest(
            reference: $payout->reference,
            amount: (float) $payout->amount,
            currency: $payout->currency,
            phone: $msisdn,
            country: $country,
            network: $payout->network,
            customerName: $payout->beneficiary_name,
            description: $payout->reason ?: 'MOKILI TOUR',
            callbackUrl: $this->webhookUrl($gateway),
        );

        try {
            $result = $this->manager->gateway($gateway)->payout($request);
        } catch (Throwable $e) {
            $payout->update(['status' => 'failed', 'failure_reason' => mb_substr($e->getMessage(), 0, 250)]);
            report($e);

            throw $e instanceof PaymentGatewayException ? $e : new PaymentGatewayException('Le service de decaissement est indisponible, reessayez.');
        }

        $this->applyToPayout($payout, $result);

        return $payout->fresh();
    }

    public function refreshPayment(Payment $payment): Payment
    {
        if (! in_array($payment->status, ['pending', 'processing'], true) || ! isset(PaymentGatewayManager::GATEWAYS[$payment->provider])) {
            return $payment;
        }

        $gateway = $this->manager->gateway($payment->provider);
        $payment->update(['last_checked_at' => now()]);

        if (! $gateway->supportsStatusCheck() || ! $payment->reference) {
            return $payment;
        }

        try {
            $this->applyToPayment($payment, $gateway->status($payment->reference, $payment->gateway_reference, 'collect'));
        } catch (Throwable $e) {
            Log::info('Payment status check failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
        }

        return $payment->fresh();
    }

    public function refreshPayout(Payout $payout): Payout
    {
        if (! in_array($payout->status, ['pending', 'processing'], true)) {
            return $payout;
        }

        $gateway = $this->manager->gateway($payout->gateway);
        $payout->update(['last_checked_at' => now()]);

        if (! $gateway->supportsStatusCheck()) {
            return $payout;
        }

        try {
            $this->applyToPayout($payout, $gateway->status($payout->reference, $payout->gateway_reference, 'payout'));
        } catch (Throwable $e) {
            Log::info('Payout status check failed', ['payout' => $payout->id, 'error' => $e->getMessage()]);
        }

        return $payout->fresh();
    }

    /** Applique un callback. Renvoie false si rien n'a ete reconnu. */
    public function handleWebhook(string $gatewayName, Request $request): bool
    {
        $result = $this->manager->gateway($gatewayName)->handleWebhook($request);

        if (! $result || ! $result->ourReference) {
            Log::warning('Unrecognised payment webhook', ['gateway' => $gatewayName, 'body' => mb_substr($request->getContent(), 0, 1000)]);

            return false;
        }

        if ($payment = Payment::where('reference', $result->ourReference)->first()) {
            $this->applyToPayment($payment, $result);

            return true;
        }

        if ($payout = Payout::where('reference', $result->ourReference)->first()) {
            $this->applyToPayout($payout, $result);

            return true;
        }

        return false;
    }

    /** Confirmation / echec manuel depuis le back-office (passerelle sans webhook). */
    public function markPayment(Payment $payment, bool $paid, ?string $reason = null): Payment
    {
        $this->applyToPayment($payment, $paid
            ? GatewayResult::success($payment->gateway_reference, ['manual' => true])
            : GatewayResult::failed($reason ?? 'Marque en echec par le back-office.', ['manual' => true]));

        return $payment->fresh();
    }

    public function applyToPayment(Payment $payment, GatewayResult $result): void
    {
        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return; // jamais revenir en arriere sur un paiement encaisse
        }

        DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status' => match ($result->status) {
                    GatewayResult::SUCCESS => 'paid',
                    GatewayResult::FAILED => 'failed',
                    default => 'processing',
                },
                'gateway_reference' => $result->providerReference ?? $payment->gateway_reference,
                'gateway_response' => $result->raw ?: $payment->gateway_response,
                'failure_reason' => $result->status === GatewayResult::FAILED ? mb_substr((string) $result->message, 0, 250) : null,
                'paid_at' => $result->status === GatewayResult::SUCCESS ? now() : $payment->paid_at,
                // compatibilite avec l'ancien ecran admin Peex
                'peex_track_id' => $payment->provider === 'peex' ? $payment->reference : $payment->peex_track_id,
                'peex_request_id' => $payment->provider === 'peex' ? ($result->providerReference ?? $payment->peex_request_id) : $payment->peex_request_id,
            ]);

            $booking = $payment->booking;

            if ($booking && $result->status === GatewayResult::SUCCESS) {
                $booking->update(['status' => 'confirmed']);
            } elseif ($booking && $result->status === GatewayResult::FAILED && $booking->status !== 'confirmed') {
                $booking->update(['status' => 'awaiting_payment']);
            }
        });

        // Vols : le paiement confirme / refuse declenche (ou non) la reservation + l'emission.
        if ($result->status !== GatewayResult::PENDING) {
            FlightPaymentBridge::settled($payment->fresh(), $result->status === GatewayResult::SUCCESS);
        }
    }

    public function applyToPayout(Payout $payout, GatewayResult $result): void
    {
        if ($payout->status === 'paid') {
            return;
        }

        $payout->update([
            'status' => match ($result->status) {
                GatewayResult::SUCCESS => 'paid',
                GatewayResult::FAILED => 'failed',
                default => 'processing',
            },
            'gateway_reference' => $result->providerReference ?? $payout->gateway_reference,
            'gateway_response' => $result->raw ?: $payout->gateway_response,
            'failure_reason' => $result->status === GatewayResult::FAILED ? mb_substr((string) $result->message, 0, 250) : null,
            'paid_at' => $result->status === GatewayResult::SUCCESS ? now() : $payout->paid_at,
        ]);
    }

    public function webhookUrl(string $gateway): string
    {
        return rtrim((string) config('app.url'), '/')."/api/webhooks/payments/{$gateway}";
    }
}
