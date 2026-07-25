<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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

        // MySQL < 5.7.7 / MariaDB < 10.2.2 default to utf8mb4 with a
        // 767-byte index key limit, which overflows on a 255-char unique
        // string column ("La cle est trop longue"). Capping the default
        // string length to 191 chars keeps unique/index columns (users.email,
        // *.slug, etc.) under that limit without touching every migration.
        Schema::defaultStringLength(191);
    }
}
