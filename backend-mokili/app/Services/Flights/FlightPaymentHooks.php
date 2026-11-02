<?php

namespace App\Services\Flights;

use App\Models\Booking;
use App\Models\Flights\FlightBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Liaison entre le paiement (PaymentService, commun a tous les modules) et
 * la machine a etats de la billetterie.
 */
class FlightPaymentHooks
{
    public function __construct(
        protected FlightStateMachine $machine,
        protected FlightFulfillmentService $fulfillment,
        protected FlightNotifier $notifier,
    ) {}

    public function paymentConfirmed(Booking $booking): void
    {
        $flight = $booking->bookable;
        if (! $flight instanceof FlightBooking) {
            return;
        }

        $wasCancelled = $flight->fresh()->status === FlightStatus::CANCELLED;
        $moved = $this->machine->tryTransition($flight, FlightStatus::PAYMENT_CONFIRMED, ['paid_at' => now()], 'Paiement confirme par le PSP', actorType: 'psp');
        if (! $moved) {
            return; // notification en double / dossier deja plus loin : rien a faire
        }

        if ($wasCancelled) {
            $flight->update(['needs_attention' => true, 'attention_reason' => 'Paiement recu sur un dossier annule : reserver ou rembourser']);
            $this->notifier->alertOps($flight->fresh(), 'Paiement recu sur un dossier annule');

            return;
        }

        // Apres commit : la reservation fournisseur ne doit jamais partir
        // sur un paiement qui serait finalement annule par un rollback.
        DB::afterCommit(function () use ($flight) {
            $this->notifier->statusChanged($flight->fresh());
            try {
                $this->fulfillment->process($flight->fresh());
            } catch (Throwable $e) {
                report($e);
                Log::error('Flight fulfillment failed', ['flight' => $flight->reference, 'error' => $e->getMessage()]);
                $flight->update(['needs_attention' => true, 'attention_reason' => 'Erreur technique apres paiement : verifier le dossier']);
            }
        });
    }

    public function paymentFailed(Booking $booking): void
    {
        $flight = $booking->bookable;
        if ($flight instanceof FlightBooking && $flight->fresh()->status === FlightStatus::PAYMENT_PENDING) {
            $this->machine->transition($flight, FlightStatus::PRICE_CONFIRMED, note: 'Paiement echoue ou refuse', actorType: 'psp');
        }
    }
}
