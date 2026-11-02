<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightBooking;
use App\Notifications\FlightOpsAlert;
use App\Notifications\FlightStatusChanged;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Notifications client (e-mail + SMS) et alertes equipe. Les messages
 * refletent le statut reel : jamais "billet emis" avant TICKETED.
 */
class FlightNotifier
{
    /** Statuts qui declenchent un message au client. */
    public const NOTIFIED = [
        FlightStatus::PAYMENT_CONFIRMED, FlightStatus::BOOKED, FlightStatus::TICKETED, FlightStatus::FAILED,
        FlightStatus::CANCELLED, FlightStatus::REFUND_PENDING, FlightStatus::REFUNDED,
    ];

    public function statusChanged(FlightBooking $flight): void
    {
        if (! in_array($flight->status, self::NOTIFIED, true)) {
            return;
        }

        try {
            if ($flight->contact_email) {
                Notification::route('mail', $flight->contact_email)->notify(new FlightStatusChanged($flight));
            }
        } catch (Throwable $e) {
            Log::warning('Flight mail notification failed', ['flight' => $flight->reference, 'error' => $e->getMessage()]);
        }

        try {
            if ($flight->contact_phone) {
                app(SmsGateway::class)->send($flight->contact_phone, self::smsText($flight));
            }
        } catch (Throwable $e) {
            Log::warning('Flight SMS notification failed', ['flight' => $flight->reference, 'error' => $e->getMessage()]);
        }
    }

    public function alertOps(FlightBooking $flight, string $reason): void
    {
        Log::warning('Billetterie : intervention requise', ['flight' => $flight->reference, 'status' => $flight->status, 'reason' => $reason]);

        try {
            if ($email = config('flights.ops_email')) {
                Notification::route('mail', $email)->notify(new FlightOpsAlert($flight, $reason));
            }
        } catch (Throwable $e) {
            Log::warning('Flight ops alert failed', ['flight' => $flight->reference, 'error' => $e->getMessage()]);
        }
    }

    public static function message(FlightBooking $flight): string
    {
        return match ($flight->status) {
            FlightStatus::PAYMENT_CONFIRMED => "Votre paiement est confirme. Nous reservons maintenant votre vol ; vous recevrez un message des que la reservation sera confirmee.",
            FlightStatus::BOOKED => "Votre reservation est confirmee (PNR {$flight->pnr}). L'emission de votre billet est en cours : votre billet electronique vous sera envoye des son emission.",
            FlightStatus::TICKETED => "Votre billet electronique est emis (PNR {$flight->pnr}). Retrouvez-le dans votre espace MOKILI TOUR.",
            FlightStatus::FAILED => "Un incident est survenu sur votre dossier. Notre equipe billetterie vous contacte pour une solution ou un remboursement.",
            FlightStatus::CANCELLED => 'Votre dossier a ete annule.',
            FlightStatus::REFUND_PENDING => 'Votre remboursement est en cours de traitement.',
            FlightStatus::REFUNDED => 'Votre remboursement de '.number_format((float) $flight->refunded_amount, 0, ',', ' ')." {$flight->currency} a ete effectue.",
            default => $flight->status_label,
        };
    }

    public static function smsText(FlightBooking $flight): string
    {
        return mb_substr("MOKILI TOUR {$flight->reference} : ".self::message($flight), 0, 300);
    }
}
