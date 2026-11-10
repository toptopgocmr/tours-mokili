<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * pawaPay Merchant API v2 - https://docs.pawapay.io/v2
 *
 *  - Collecte  : POST /v2/deposits  (depositId UUID, payer MMO)
 *  - Decaissement : POST /v2/payouts (payoutId UUID, recipient MMO)
 *  - Statut    : GET /v2/deposits/{id} | GET /v2/payouts/{id}
 *  - Callback  : POST vers l'URL configuree dans le dashboard pawaPay
 *    (depositId|payoutId + status COMPLETED/FAILED). Par securite le statut
 *    est TOUJOURS re-verifie via l'API avant d'etre applique.
 *
 * Congo-Brazzaville : providers MTN_MOMO_COG / AIRTEL_COG (XAF).
 * Numeros de test sandbox : https://docs.pawapay.io/v2/docs/test_numbers
 */
class PawaPayGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'pawapay';
    }

    public function isConfigured(): bool
    {
        return filled($this->config('token'));
    }

    public function collect(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post('/v2/deposits', [
            'depositId' => $request->reference,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $request->phone,
                    'provider' => $this->provider($request),
                ],
            ],
            'amount' => (string) $request->integerAmount(),
            'currency' => $this->currencyFor($request),
            'clientReferenceId' => $request->reference,
            'customerMessage' => $this->customerMessage(),
        ]);

        return $this->initiationResult($response, 'depositId');
    }

    public function payout(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post('/v2/payouts', [
            'payoutId' => $request->reference,
            'recipient' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $request->phone,
                    'provider' => $this->provider($request),
                ],
            ],
            'amount' => (string) $request->integerAmount(),
            'currency' => $this->currencyFor($request),
            'clientReferenceId' => $request->reference,
            'customerMessage' => $this->customerMessage(),
        ]);

        return $this->initiationResult($response, 'payoutId');
    }

    /** XAF -> XOF pour l'Afrique de l'Ouest (meme parite, montant inchange). */
    protected function currencyFor(PaymentRequest $request): string
    {
        return $request->currency === 'XAF' && in_array($request->country, $this->config('xof_countries') ?? [], true)
            ? 'XOF' : $request->currency;
    }

    public function status(string $reference, ?string $providerReference, string $type = 'collect'): GatewayResult
    {
        $this->ensureConfigured();

        $path = $type === 'payout' ? "/v2/payouts/{$reference}" : "/v2/deposits/{$reference}";
        $response = $this->http()->get($path);

        if ($response->failed()) {
            $this->fail($response, 'Impossible de recuperer le statut pawaPay.');
        }

        $json = $response->json() ?? [];

        if (($json['status'] ?? null) === 'NOT_FOUND') {
            return GatewayResult::failed('Transaction introuvable chez pawaPay.', $json);
        }

        $data = $json['data'] ?? [];
        $status = GatewayResult::mapStatus($data['status'] ?? null);

        return new GatewayResult(
            $status,
            $data['providerTransactionId'] ?? $reference,
            $status === GatewayResult::FAILED
                ? ($data['failureReason']['failureMessage'] ?? 'Paiement refuse ou annule.')
                : null,
            $json,
            $reference,
        );
    }

    public function handleWebhook(Request $request): ?GatewayResult
    {
        $payload = $request->json()->all();
        $reference = $payload['depositId'] ?? $payload['payoutId'] ?? $payload['refundId'] ?? null;

        if (! $reference) {
            return null;
        }

        $type = isset($payload['payoutId']) ? 'payout' : 'collect';

        // On ne fait pas confiance au corps du callback : on redemande le
        // statut a pawaPay avec notre token.
        try {
            $confirmed = $this->status($reference, null, $type);

            return new GatewayResult($confirmed->status, $confirmed->providerReference, $confirmed->message, $payload, $reference);
        } catch (PaymentGatewayException) {
            return null;
        }
    }

    protected function provider(PaymentRequest $request): string
    {
        $provider = $this->networkCode($request->country, $request->network);

        if ($provider) {
            return $provider;
        }

        // Pas de correspondance connue : on laisse pawaPay deviner
        // l'operateur a partir du numero (POST /v2/predict-provider).
        $response = $this->http()->post('/v2/predict-provider', ['phoneNumber' => $request->phone]);

        if ($response->failed() || ! $response->json('provider')) {
            throw new PaymentGatewayException('Operateur Mobile Money non pris en charge par pawaPay pour ce numero.');
        }

        return (string) $response->json('provider');
    }

    protected function initiationResult($response, string $idField): GatewayResult
    {
        $json = $response->json() ?? [];

        if ($response->failed() && empty($json['status'])) {
            $this->fail($response, 'pawaPay a refuse la demande.');
        }

        return match ($json['status'] ?? null) {
            'ACCEPTED', 'DUPLICATE_IGNORED' => GatewayResult::pending($json[$idField] ?? null, $json),
            'REJECTED' => GatewayResult::failed($json['failureReason']['failureMessage'] ?? 'Demande refusee par pawaPay.', $json),
            default => GatewayResult::pending($json[$idField] ?? null, $json),
        };
    }

    protected function customerMessage(): string
    {
        $msg = preg_replace('/[^A-Za-z0-9 ]/', '', (string) $this->config('customer_message', 'MOKILI TOUR'));

        return str_pad(mb_substr($msg, 0, 22), 4, ' ');
    }

    protected function http()
    {
        return Http::baseUrl(rtrim((string) $this->config('base_url'), '/'))
            ->withToken((string) $this->config('token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) $this->config('timeout', 20));
    }
}
