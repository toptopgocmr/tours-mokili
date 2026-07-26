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

        // Ziggy's route() list (via the global url()/route() helpers) and
        // Vite's asset tags (script/link/prefetch) are generated server-side
        // from Laravel's URL generator. By default that generator derives
        // BOTH the host and the scheme from the current Request object at
        // runtime - and on Railway/FrankenPHP that request-level scheme
        // detection kept resolving to http:// no matter what we did further
        // down the stack (trusted proxies, middleware mutating $_SERVER
        // before Laravel even boots, etc.), so every asset/route URL kept
        // rendering as http:// and got blocked as mixed content on the
        // https:// page.
        //
        // forceRootUrl() replaces the *entire* root (scheme + host) with a
        // fixed string taken straight from config('app.url') - it doesn't
        // ask the Request anything, so it can't be defeated by however
        // FrankenPHP/Railway's proxy is (mis)reporting the scheme per
        // request. forceScheme() is kept alongside it as a second guarantee
        // for any URL helper call that only consults the scheme in
        // isolation.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceRootUrl(config('app.url'));
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
