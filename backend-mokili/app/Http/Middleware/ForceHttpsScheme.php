<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * URL::forceScheme() only affects Laravel's own url()/route()/asset()
 * helpers - it does NOT affect $request->isSecure() / getSchemeAndHttpHost(),
 * which Ziggy calls directly to build its JS route list. Railway's edge
 * terminates TLS and proxies plain HTTP to the container; trusting the
 * proxy (bootstrap/app.php) should make isSecure() read X-Forwarded-Proto
 * correctly, but in practice that header wasn't being honored, so every
 * Ziggy/Vite URL kept rendering as http:// and got blocked as mixed
 * content. Setting the HTTPS server var directly, first thing on every
 * request, makes isSecure() (and everything downstream of it) return
 * true unconditionally - no dependency on proxy headers at all.
 */
class ForceHttpsScheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            $request->server->set('HTTPS', 'on');
        }

        return $next($request);
    }
}
