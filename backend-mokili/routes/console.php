<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Billetterie aerienne : verification aupres du fournisseur des dossiers
// restes en attente (timeout, traitement manuel) - jamais de relance
// aveugle, voir FlightFulfillmentService::reconcile().
Artisan::command('flights:reconcile {reference?}', function (?string $reference = null) {
    $service = app(\App\Services\Flights\FlightFulfillmentService::class);
    $minutes = (int) config('flights.reconcile_after_minutes', 10);

    $query = \App\Models\Flights\FlightBooking::query();
    if ($reference) {
        $query->where('reference', $reference);
    } else {
        $query->whereIn('status', [
            \App\Services\Flights\FlightStatus::PAYMENT_CONFIRMED,
            \App\Services\Flights\FlightStatus::BOOKING_PENDING,
            \App\Services\Flights\FlightStatus::TICKETING_PENDING,
        ])->where('updated_at', '<=', now()->subMinutes($minutes));
    }

    $query->each(function ($flight) use ($service) {
        $before = $flight->status;
        $after = $service->reconcile($flight)->status;
        $this->line("{$flight->reference} : {$before} -> {$after}");
    });
})->purpose('Verifie aupres des fournisseurs les dossiers de billetterie en attente');

\Illuminate\Support\Facades\Schedule::command('flights:reconcile')->everyFiveMinutes()->withoutOverlapping();
