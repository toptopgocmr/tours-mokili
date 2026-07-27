<?php

namespace App\Providers;

use App\Services\Peex\PeexClient;
use Illuminate\Support\ServiceProvider;

class PeexServiceProvider extends ServiceProvider
{
    /**
     * Register the Peex HTTP client as a singleton so the same
     * configured instance (base URL + secret key) is reused across
     * the app (web checkout flow, API controllers, jobs, ...).
     */
    public function register(): void
    {
        $this->app->singleton(PeexClient::class, function () {
            return new PeexClient(
                baseUrl: config('services.peex.base_url'),
                secretKey: config('services.peex.secret_key'),
                timeout: config('services.peex.timeout', 15),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
