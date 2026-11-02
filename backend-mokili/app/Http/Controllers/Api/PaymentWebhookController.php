<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Callbacks des agregateurs :
 *   POST /api/webhooks/payments/{peex|pawapay|maishapay|digitwace}
 * Toujours 200 quand le message est traite (sinon les agregateurs
 * renvoient le callback en boucle).
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentService $payments): JsonResponse
    {
        abort_unless(array_key_exists($gateway, PaymentGatewayManager::GATEWAYS), 404);

        $handled = $payments->handleWebhook($gateway, $request);

        return response()->json(['received' => true, 'handled' => $handled], $handled ? 200 : 202);
    }
}
