<?php

namespace App\Notifications;

use App\Models\Flights\FlightBooking;
use App\Services\Flights\FlightNotifier;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlightStatusChanged extends Notification
{
    public function __construct(public FlightBooking $flight) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $f = $this->flight;

        return (new MailMessage)
            ->subject("MOKILI TOUR - {$f->title} - {$f->status_label} ({$f->reference})")
            ->greeting('Bonjour '.($f->contact_name ?: ''))
            ->line(FlightNotifier::message($f))
            ->line('Dossier : '.$f->reference.($f->pnr ? ' - PNR : '.$f->pnr : ''))
            ->action('Voir mon dossier', url('/voyage/vols/dossiers/'.$f->reference))
            ->line('Merci de voyager avec MOKILI TOUR.');
    }
}
