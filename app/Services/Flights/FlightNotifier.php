<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightNotification;
use App\Models\Flights\FlightOrder;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifications client (e-mail + SMS + enregistrement applicatif).
 * Regle : les messages refletent le STATUT REEL ; "billet emis" n'est
 * jamais annonce avant TICKETED.
 */
class FlightNotifier
{
    public const EVENTS = [
        'payment_confirmed' => 'Paiement recu pour votre dossier %s. Nous reservons votre vol, le billet n\'est pas encore emis.',
        'booked' => 'Votre vol est reserve (dossier %s). L\'emission du billet est en cours, nous vous previendrons.',
        'ticketed' => 'Votre billet electronique est emis (dossier %s). Retrouvez-le dans votre espace Mes vols.',
        'failed' => 'Votre dossier %s demande une verification de notre equipe. Aucun montant supplementaire ne sera debite.',
        'cancelled' => 'Votre dossier %s a ete annule.',
        'refund_pending' => 'Votre demande de remboursement pour le dossier %s est en cours de traitement.',
        'flight_delayed' => 'Un retard est annonce sur votre vol (dossier %s). Consultez Mes vols pour le detail.',
        'flight_cancelled' => 'Votre vol a ete annule ou deroute par la compagnie (dossier %s). Notre equipe vous contacte pour une solution ou un remboursement.',
        'refunded' => 'Le remboursement de votre dossier %s a ete effectue.',
    ];

    public function notify(FlightOrder $order, string $event): void
    {
        $template = self::EVENTS[$event] ?? null;
        if (! $template) {
            return;
        }

        $message = 'MOKILI TOUR - '.sprintf($template, $order->reference);

        $this->send($order, 'app', $event, $message, fn () => null);

        if ($order->contact_email) {
            $this->send($order, 'email', $event, $message, function () use ($order, $message) {
                Mail::raw($message, fn ($mail) => $mail->to($order->contact_email)->subject('MOKILI TOUR - Vol '.$order->reference));
            });
        }

        if ($order->contact_phone) {
            $this->send($order, 'sms', $event, $message, function () use ($order, $message) {
                app(SmsGateway::class)->send($order->contact_phone, $message);
            });
        }
    }

    private function send(FlightOrder $order, string $channel, string $event, string $message, callable $sender): void
    {
        $notification = FlightNotification::create([
            'flight_order_id' => $order->id,
            'user_id' => $order->user_id,
            'channel' => $channel,
            'event' => $event,
            'message' => $message,
            'result' => 'queued',
        ]);

        try {
            $sender();
            $notification->update(['result' => 'sent']);
        } catch (Throwable $e) {
            // Un echec d'envoi ne doit jamais bloquer le traitement du dossier.
            report($e);
            $notification->update(['result' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);
        }
    }
}
