<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Ziggy's route() list and Vite's asset tags (script/link/prefetch)
        // are generated server-side from Laravel's URL generator, which by
        // default mirrors the current request's detected scheme. Railway's
        // proxy wasn't reliably surfacing that as https even with proxies
        // trusted, so every asset/route URL rendered as http:// and got
        // blocked as mixed content on the https:// page. Forcing the
        // scheme here is unconditional and doesn't depend on proxy headers.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // MySQL < 5.7.7 / MariaDB < 10.2.2 default to utf8mb4 with a
        // 767-byte index key limit, which overflows on a 255-char unique
        // string column ("La cle est trop longue"). Capping the default
        // string length to 191 chars keeps unique/index columns (users.email,
        // *.slug, etc.) under that limit without touching every migration.
        Schema::defaultStringLength(191);
    }
}
