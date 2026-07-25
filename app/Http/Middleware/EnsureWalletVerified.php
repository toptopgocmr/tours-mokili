<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks checkout/payment routes until the current user has a fresh,
 * successful Peex wallet verification on file (see
 * App\Services\Peex\PeexClient::verifyWallet and
 * App\Http\Controllers\Api\WalletVerificationController).
 */
class EnsureWalletVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $wallet = $request->user()?->wallet;

        if (! $wallet || ! $wallet->peex_verified_at) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Veuillez verifier votre portefeuille Peex avant de continuer.',
                ], 409)
                : back()->with('error', 'Veuillez verifier votre portefeuille Peex avant de continuer.');
        }

        return $next($request);
    }
}
