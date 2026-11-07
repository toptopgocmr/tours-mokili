<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightOrder;
use App\Models\Payment;
use Throwable;

/** Relie PaymentService (verification serveur / webhooks) aux commandes de fret. */
class FreightPaymentBridge
{
    public static function initiated(Payment $payment): void
    {
        $order = self::orderFor($payment);
        if (! $order) {
            return;
        }

        try {
            app(FreightOrderService::class)->paymentInitiated($order);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function settled(Payment $payment, bool $paid): void
    {
        $order = self::orderFor($payment);
        if (! $order) {
            return;
        }

        try {
            app(FreightOrderService::class)->paymentSettled($order->fresh(), $payment, $paid);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function orderFor(Payment $payment): ?FreightOrder
    {
        $booking = $payment->booking;

        return ($booking && $booking->bookable_type === FreightOrder::class) ? FreightOrder::find($booking->bookable_id) : null;
    }
}
