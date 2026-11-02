<?php

namespace App\Notifications;

use App\Models\Flights\FlightBooking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlightOpsAlert extends Notification
{
    public function __construct(public FlightBooking $flight, public string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Billetterie] Action requise - {$this->flight->reference}")
            ->line($this->reason)
            ->line("Statut : {$this->flight->status} - Montant : {$this->flight->total_amount} {$this->flight->currency}")
            ->action('Ouvrir le dossier', url('/admin/vols/'.$this->flight->reference));
    }
}
