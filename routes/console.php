<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vols : relecture des reservations fournisseur + suivi des vols (necessite `php artisan schedule:work` ou un cron).
Illuminate\Support\Facades\Schedule::command('flights:sync')->everyThirtyMinutes()->withoutOverlapping();
