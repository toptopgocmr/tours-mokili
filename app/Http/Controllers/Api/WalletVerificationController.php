<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyWalletRequest;
use App\Models\PeexVerification;
use App\Models\Wallet;
use App\Services\Peex\PeexClient;
use App\Services\Peex\PeexException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Collects and verifies the customer's mobile money / bank wallet via
 * Peex (POST clients/verify-wallet) ahead of any checkout, for every
 * service module. Used by both the web checkout page (Inertia form
 * post) and the Flutter mobile app (JSON API, Sanctum token auth).
 *
 * Docs: https://peex-api-docs.peexit.com/verify-wallet
 */
class WalletVerificationController extends Controller
{
    public function __construct(protected PeexClient $peex) {}

    public function store(VerifyWalletRequest $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $result = $this->peex->verifyWallet(
                countryCode: $request->string('country_code'),
                accountNumber: $request->string('account_number'),
            );

            $wallet = DB::transaction(function () use ($user, $request, $result) {
                PeexVerification::create([
                    'user_id' => $user->id,
                    'country_code' => strtoupper($request->string('country_code')),
                    'account_number' => $request->string('account_number'),
                    'is_valid' => $result['isValid'],
                    'account_name' => $result['accountName'],
                    'operator' => $result['operator'],
                    'status' => $result['status'],
                    'http_status' => 200,
                    'raw_response' => $result,
                ]);

                return Wallet::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'country_code' => strtoupper($request->string('country_code')),
                        'account_number' => $request->string('account_number'),
                        'operator' => $result['operator'],
                        'account_name' => $result['accountName'],
                        'peex_status' => $result['status'],
                        'peex_verified_at' => $result['isValid'] ? now() : null,
                    ]
                );
            });

            return response()->json([
                'message' => $result['isValid']
                    ? 'Portefeuille verifie avec succes.'
                    : 'Ce portefeuille ne peut pas recevoir de transactions.',
                'wallet' => $wallet,
                'peex' => $result,
            ], $result['isValid'] ? 200 : 422);
        } catch (PeexException $e) {
            PeexVerification::create([
                'user_id' => $user->id,
                'country_code' => strtoupper($request->string('country_code')),
                'account_number' => $request->string('account_number'),
                'is_valid' => false,
                'http_status' => $e->statusCode,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], $e->statusCode ?: 502);
        }
    }
}
