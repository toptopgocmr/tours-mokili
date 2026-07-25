<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->statefulApi();

        // Railway (and most PaaS hosts) terminate HTTPS at their edge and
        // forward plain HTTP to the container. Without trusting that proxy,
        // Laravel thinks every request is HTTP and generates asset/route
        // URLs as http://, which browsers then block as mixed content on
        // an https:// page. Trusting all proxies here (safe: the container
        // isn't directly reachable from the internet, only via Railway's
        // edge) makes Laravel honor X-Forwarded-Proto correctly.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'peex.verified' => \App\Http\Middleware\EnsureWalletVerified::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
