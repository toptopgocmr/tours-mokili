<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightCommission;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightRefund;
use App\Models\Flights\FlightStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Remboursements. Le statut du billet, de la demande apres-vente
 * (flight_refunds.status) et du remboursement sont suivis separement.
 * Aucun PSP du projet n'expose de remboursement automatique : la
 * procedure est MANUELLE et controlee (back-office), mode "auto" reserve
 * pour un connecteur futur.
 */
class FlightRefundService
{
    public function __construct(
        protected FlightStateMachine $machine,
        protected FlightNotifier $notifier,
    ) {}

    public function request(
        FlightOrder $order,
        string $reasonCode,
        ?string $reason,
        string $actor,
        ?float $amount = null,
        ?Payment $payment = null,
        float $supplierFee = 0,
        float $pspFee = 0,
        float $penalty = 0,
        bool $moveOrder = true,
    ): FlightRefund {
        $payment ??= $order->booking?->payments()->where('status', 'paid')->latest()->first();
        $requested = $amount ?? (float) $order->price_total;
        $refundable = max($requested - $supplierFee - $pspFee - $penalty, 0);

        return DB::transaction(function () use ($order, $reasonCode, $reason, $actor, $payment, $requested, $refundable, $supplierFee, $pspFee, $penalty, $moveOrder) {
            $refund = FlightRefund::create([
                'flight_order_id' => $order->id,
                'payment_id' => $payment?->id,
                'reason_code' => $reasonCode,
                'reason' => $reason,
                'requested_amount' => $requested,
                'supplier_fee' => $supplierFee,
                'psp_fee' => $pspFee,
                'penalty' => $penalty,
                'refundable_amount' => $refundable,
                'mode' => 'manual',
                'status' => 'requested',
            ]);

            if ($moveOrder && FlightStatus::canTransition($order->status, FlightStatus::REFUND_PENDING)) {
                $this->machine->transition($order, FlightStatus::REFUND_PENDING, $actor, $reason ?? $reasonCode);
            }

            FlightAudit::log('refund.requested', $order, [
                'refund_id' => $refund->id, 'code' => $reasonCode, 'requested' => $requested, 'refundable' => $refundable,
            ], $actor);
            $this->notifier->notify($order, 'refund_pending');

            return $refund;
        });
    }

    /** Remboursement effectue (confirme par le back-office / le PSP). */
    public function markRefunded(FlightRefund $refund, int $adminId): FlightRefund
    {
        return DB::transaction(function () use ($refund, $adminId) {
            $order = $refund->order;

            $refund->update(['status' => 'refunded', 'processed_by' => $adminId, 'processed_at' => now()]);

            // Remboursement du dossier entier ; un simple doublon de paiement ne change pas le statut du dossier.
            $fullRefund = $order->status === FlightStatus::REFUND_PENDING;

            if ($fullRefund) {
                $this->machine->transition($order, FlightStatus::REFUNDED, 'admin:'.$adminId, 'Remboursement confirme');
            }

            if ($refund->payment_id && $payment = Payment::find($refund->payment_id)) {
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_reason' => 'Vol '.$order->reference.' : '.($refund->reason ?? $refund->reason_code),
                ]);
            }
            if ($fullRefund) {
                $order->booking?->update(['status' => 'refunded']);
                FlightCommission::where('flight_order_id', $order->id)->update(['status' => 'reversed']);
            }

            FlightAudit::log('refund.completed', $order, ['refund_id' => $refund->id, 'amount' => (string) $refund->refundable_amount], 'admin:'.$adminId);
            if ($fullRefund) {
                $this->notifier->notify($order, 'refunded');
            }

            return $refund->fresh();
        });
    }

    public function reject(FlightRefund $refund, int $adminId, string $reason): FlightRefund
    {
        $refund->update(['status' => 'rejected', 'processed_by' => $adminId, 'processed_at' => now(), 'reason' => $reason]);
        FlightAudit::log('refund.rejected', $refund->order, ['refund_id' => $refund->id], 'admin:'.$adminId);

        return $refund->fresh();
    }
}
