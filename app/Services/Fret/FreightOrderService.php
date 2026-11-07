<?php

namespace App\Services\Fret;

use App\Models\Booking;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\FreightOrder;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\Fret\Quote;
use App\Models\Fret\Shipment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Commandes de fret : devis accepte -> paiement (verifie cote serveur) ->
 * reservation confirmee par le partenaire. Chaque statut est distinct et
 * toute transition est controlee et journalisee.
 */
class FreightOrderService
{
    public function __construct(private FreightNotifier $notifier) {}

    /** Commande depuis un devis accepte (conditions figees). */
    public function createFromQuote(User $user, Quote $quote): FreightOrder
    {
        $request = $quote->request;
        $this->assertPartnerApproved($quote->partner_user_id);

        return DB::transaction(function () use ($user, $quote, $request) {
            // Verrou : un devis ne peut etre accepte qu'une fois (double clic, double appareil).
            $locked = Quote::whereKey($quote->id)->lockForUpdate()->first();
            if ($locked->status !== 'sent') {
                throw new RuntimeException('Ce devis a deja ete traite.');
            }

            $terms = [
                'title' => "Fret {$request->mode} {$request->origin_city} -> {$request->destination_city}",
                'mode' => $request->mode,
                'origin' => $request->origin_city.' ('.$request->origin_country.')',
                'destination' => $request->destination_city.' ('.$request->destination_country.')',
                'quote_reference' => $quote->reference,
                'quote_version' => $quote->version,
                'lines' => $quote->lines,
                'currency' => $quote->currency,
                'total' => (string) $quote->total_amount,
                'is_firm' => $quote->is_firm,
                'valid_until' => $quote->valid_until->toDateString(),
                'transit_days' => $quote->transit_days,
                'conditions' => $quote->conditions,
                'exclusions' => $quote->exclusions,
                'cargo' => $request->only(['cargo_type', 'description', 'weight_kg', 'volume_m3', 'dimensions', 'packages', 'load_type', 'container_type', 'declared_value', 'dangerous_goods']),
                'accepted_at' => now()->toIso8601String(),
            ];

            $quote->update(['status' => 'accepted', 'accepted_at' => now(), 'accepted_snapshot' => $terms]);
            // Les autres propositions ouvertes sur la meme demande sont closes : un seul devis retenu.
            Quote::where('quote_request_id', $request->id)->where('id', '!=', $quote->id)->where('status', 'sent')->update(['status' => 'closed']);
            $request->update(['status' => 'DEVIS_ACCEPTE']);

            $order = $this->build($user, (int) $quote->partner_user_id, (float) $quote->total_amount, $quote->currency, $terms, [
                'quote_id' => $quote->id,
                'freight_offer_id' => $request->freight_offer_id,
            ], [
                'mode' => $request->mode, 'origin_city' => $request->origin_city, 'origin_country' => $request->origin_country,
                'destination_city' => $request->destination_city, 'destination_country' => $request->destination_country,
                'weight_kg' => $request->weight_kg ?: 1, 'dimensions' => $request->dimensions,
            ]);

            FreightAudit::log('quote.accepted', $quote, 'user:'.$user->id, null, ['order' => $order->reference]);

            return $order;
        });
    }

    /** Tarif instantane : reservation directe depuis la grille du partenaire (prix recalcule cote serveur). */
    public function createInstant(User $user, FreightOffer $offer, array $measures, ?string $notes = null): FreightOrder
    {
        if (! $offer->is_active || $offer->status !== 'published' || $offer->pricing_mode === 'quote') {
            throw new RuntimeException('Cette prestation n\'est pas reservable en ligne : demandez un devis.');
        }
        if ($offer->valid_until && $offer->valid_until->endOfDay()->isPast()) {
            throw new RuntimeException('Cette offre a expire.');
        }
        if ($offer->carrier_id) {
            $this->assertPartnerApproved($offer->carrier_id);
        }

        $calc = FreightPricing::instant($offer, $measures);
        if ($calc['total'] <= 0) {
            throw new RuntimeException('Mesures invalides : impossible de calculer le tarif.');
        }
        if ($offer->capacity_kg && (float) ($measures['weight_kg'] ?? 0) > $offer->capacity_kg) {
            throw new RuntimeException('Le poids depasse la capacite de cette offre.');
        }

        $terms = [
            'title' => $offer->title,
            'mode' => $offer->mode,
            'origin' => $offer->origin_city.' ('.$offer->origin_country.')',
            'destination' => $offer->destination_city.' ('.$offer->destination_country.')',
            'offer_id' => $offer->id,
            'lines' => $calc['lines'],
            'currency' => $offer->currency,
            'total' => (string) $calc['total'],
            'is_firm' => $calc['is_firm'], // false = estimation : a reverifier avec le partenaire
            'valid_until' => $offer->valid_until?->toDateString(),
            'transit_days' => $offer->transit_days_max,
            'conditions' => $offer->restrictions,
            'exclusions' => $offer->exclusions,
            'cargo' => $measures + ['notes' => $notes],
            'accepted_at' => now()->toIso8601String(),
        ];

        return DB::transaction(fn () => $this->build($user, (int) ($offer->carrier_id ?? 0), $calc['total'], $offer->currency, $terms, [
            'freight_offer_id' => $offer->id,
        ], [
            'mode' => $offer->mode, 'origin_city' => $offer->origin_city, 'origin_country' => $offer->origin_country,
            'destination_city' => $offer->destination_city, 'destination_country' => $offer->destination_country,
            'weight_kg' => $measures['weight_kg'] ?? 1, 'dimensions' => $measures['dimensions'] ?? null,
            'package_type' => $measures['package_type'] ?? null, 'package_description' => $measures['package_description'] ?? null,
        ]));
    }

    private function build(User $user, int $partnerId, float $total, string $currency, array $terms, array $extra, array $shipmentData): FreightOrder
    {
        $rate = $this->rateFor($partnerId);
        $commission = round($total * $rate / 100, 2);

        $shipment = Shipment::create($shipmentData + [
            'user_id' => $user->id,
            'partner_user_id' => $partnerId ?: null,
            'status' => 'enregistre',
            'estimated_price' => $total,
            'currency' => $currency,
        ]);

        $order = FreightOrder::create($extra + [
            'user_id' => $user->id,
            'partner_user_id' => $partnerId ?: null,
            'shipment_id' => $shipment->id,
            'status' => FreightOrder::ACCEPTED,
            'currency' => $currency,
            'total_amount' => $total,
            'commission_percent' => $rate,
            'commission_amount' => $commission,
            'partner_amount' => round($total - $commission, 2),
            'frozen_terms' => $terms,
            'accepted_at' => now(),
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'bookable_type' => FreightOrder::class,
            'bookable_id' => $order->id,
            'quantity' => 1,
            'unit_price' => $total,
            'total_amount' => $total,
            'currency' => $currency,
            'status' => 'awaiting_payment',
            'meta' => ['reference' => $order->reference, 'tracking_code' => $shipment->tracking_code, 'shipment_id' => $shipment->id],
        ]);

        $order->update(['booking_id' => $booking->id]);
        $shipment->update(['freight_order_id' => $order->id]);

        FreightAudit::log('order.created', $order, 'user:'.$user->id, null, ['total' => $total, 'commission_percent' => $rate]);
        $this->notifier->notify($user, 'order_created', $order->reference, "Commande {$order->reference} creee. Code de suivi : {$shipment->tracking_code}. Procedez au paiement.");

        return $order->fresh(['booking', 'shipment']);
    }

    // --- Paiement (appele par FreightPaymentBridge, verifie cote serveur par PaymentService) ---

    public function paymentInitiated(FreightOrder $order): void
    {
        if ($order->status === FreightOrder::ACCEPTED) {
            $this->transition($order, FreightOrder::PAYMENT_PENDING, 'system', 'Paiement initie');
        }
    }

    public function paymentSettled(FreightOrder $order, Payment $payment, bool $paid): void
    {
        if (! $paid) {
            if ($order->status === FreightOrder::PAYMENT_PENDING) {
                $this->transition($order, FreightOrder::ACCEPTED, 'webhook', 'Paiement refuse ou abandonne');
            }

            return;
        }

        // Paiement tardif / double : jamais de changement silencieux, on journalise pour rapprochement/remboursement.
        if (! in_array($order->status, [FreightOrder::ACCEPTED, FreightOrder::PAYMENT_PENDING], true)) {
            FreightAudit::log('payment.unexpected', $order, 'webhook', 'Paiement recu sur une commande au statut '.$order->status, ['payment_id' => $payment->id, 'amount' => (string) $payment->amount]);

            return;
        }

        $this->transition($order, FreightOrder::PAID, 'webhook', 'Paiement confirme ('.$payment->provider.')');
        $order->update(['payment_confirmed_at' => now()]);
        FreightAudit::log('payment.confirmed', $order, 'webhook', null, ['payment_id' => $payment->id, 'amount' => (string) $payment->amount]);

        $this->notifier->notify($order->user, 'payment_confirmed', $order->reference, "Paiement recu pour {$order->reference}. En attente de confirmation du partenaire (votre marchandise n'est pas encore prise en charge).");
        $this->notifier->notify($order->partner, 'order_paid', $order->reference, "Commande {$order->reference} payee : confirmez la reservation et les modalites de remise.");
    }

    // --- Partenaire ---

    public function confirmReservation(User $partner, FreightOrder $order, string $instructions): FreightOrder
    {
        abort_unless($order->partner_user_id === $partner->id, 403);
        if ($order->status !== FreightOrder::PAID) {
            throw new RuntimeException('Seule une commande payee peut etre confirmee.');
        }

        $this->transition($order, FreightOrder::CONFIRMED, 'user:'.$partner->id, 'Reservation confirmee');
        $order->update(['confirmed_at' => now(), 'pickup_instructions' => $instructions]);
        $this->notifier->notify($order->user, 'reservation_confirmed', $order->reference, "Reservation {$order->reference} confirmee. Instructions : {$instructions}");

        return $order->fresh();
    }

    // --- Annulation / remboursement (suivis separement) ---

    public function cancel(FreightOrder $order, string $actor, string $reason): FreightOrder
    {
        if (! $order->canTransition(FreightOrder::CANCELLED)) {
            throw new RuntimeException('Cette commande ne peut plus etre annulee.');
        }

        $this->transition($order, FreightOrder::CANCELLED, $actor, $reason);
        $order->update(['cancel_reason' => $reason]);
        $order->booking?->update(['status' => $order->payment_confirmed_at ? $order->booking->status : 'cancelled']);
        $order->shipment?->update(['status' => 'annule']);

        $paid = (bool) $order->payment_confirmed_at;
        $msg = "Commande {$order->reference} annulee.".($paid ? ' Le remboursement sera traite par notre equipe.' : '');
        $this->notifier->notify($order->user, 'order_cancelled', $order->reference, $msg);
        $this->notifier->notify($order->partner, 'order_cancelled', $order->reference, "Commande {$order->reference} annulee : {$reason}");

        return $order->fresh();
    }

    /** Remboursement effectue (par le staff) : trace separee de l'annulation. */
    public function markRefunded(FreightOrder $order, User $admin, string $reason): FreightOrder
    {
        abort_unless($order->status === FreightOrder::CANCELLED && $order->payment_confirmed_at && ! $order->refunded_at, 422, 'Remboursement impossible pour cette commande.');

        DB::transaction(function () use ($order, $admin, $reason) {
            $order->update(['refunded_at' => now(), 'settlement_ref' => null, 'settled_at' => null]);
            $order->booking?->update(['status' => 'refunded']);
            Payment::where('booking_id', $order->booking_id)->where('status', 'success')
                ->update(['status' => 'refunded', 'refunded_at' => now(), 'refund_reason' => 'Fret '.$order->reference.' : '.$reason]);
            FreightAudit::log('order.refunded', $order, 'admin:'.$admin->id, $reason, ['amount' => (string) $order->total_amount]);
        });

        $this->notifier->notify($order->user, 'order_refunded', $order->reference, "Le remboursement de {$order->reference} a ete effectue.");

        return $order->fresh();
    }

    public function transition(FreightOrder $order, string $to, string $actor, ?string $reason = null): void
    {
        if (! $order->canTransition($to)) {
            throw new RuntimeException("Transition interdite : {$order->status} -> {$to}");
        }

        $from = $order->status;
        $order->update(['status' => $to]);
        FreightAudit::log('order.status', $order, $actor, $reason, ['from' => $from, 'to' => $to]);
    }

    private function rateFor(int $partnerId): float
    {
        $profile = $partnerId ? FreightPartnerProfile::where('user_id', $partnerId)->first() : null;

        return $profile ? $profile->commissionRate() : (float) config('freight.commission_percent', 8);
    }

    private function assertPartnerApproved(?int $partnerId): void
    {
        if (! $partnerId) {
            return;
        }
        $profile = FreightPartnerProfile::where('user_id', $partnerId)->first();
        if (! $profile || ! $profile->isApproved()) {
            throw new RuntimeException('Ce partenaire n\'est pas disponible actuellement.');
        }
    }
}
