<?php

namespace App\Services\Flights;

use App\Jobs\ProcessFlightOrder;
use App\Models\Flights\FlightOrder;
use App\Models\Payment;
use Throwable;

/**
 * Pont entre PaymentService (generique, tous modules) et le moteur de vols.
 * Ne fait rien pour les reservations qui ne sont pas des dossiers de vol.
 * Toute erreur est journalisee sans jamais casser le flux de paiement.
 */
class FlightPaymentBridge
{
    public static function initiated(Payment $payment): void
    {
        $order = self::orderFor($payment);
        if (! $order) {
            return;
        }

        try {
            app(FlightOrderService::class)->paymentInitiated($order, $payment);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** $paid : true = SUCCESS, false = FAILED (les paiements en attente ne sont pas signales). */
    public static function settled(Payment $payment, bool $paid): void
    {
        $order = self::orderFor($payment);
        if (! $order) {
            return;
        }

        try {
            if (app(FlightOrderService::class)->paymentSettled($order, $payment, $paid)) {
                ProcessFlightOrder::dispatch($order->id);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function orderFor(Payment $payment): ?FlightOrder
    {
        $booking = $payment->booking;

        if (! $booking || $booking->bookable_type !== FlightOrder::class) {
            return null;
        }

        return FlightOrder::find($booking->bookable_id);
    }
}
