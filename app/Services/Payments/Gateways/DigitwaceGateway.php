<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Digitwace / WacePay - agregateur Mobile Money (MTN, Orange, Airtel,
 * Wave...) base a Douala, collecte et paiement vers portefeuilles.
 *
 * La documentation API Digitwace n'est pas publique : elle est remise au
 * marchand avec ses cles sandbox / live. Ce connecteur suit le schema REST
 * standard (cle API en Bearer, JSON, webhook signe HMAC) et tous les
 * chemins sont parametrables par variables d'environnement
 * (DIGITWACE_BASE_URL, DIGITWACE_COLLECT_PATH, DIGITWACE_PAYOUT_PATH,
 * DIGITWACE_STATUS_PATH) pour s'aligner sur la doc recue sans modifier le
 * code. Les noms de champs envoyes couvrent les variantes usuelles.
 */
class DigitwaceGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'digitwace';
    }

    public function isConfigured(): bool
    {
        return filled($this->config('base_url')) && filled($this->config('api_key'));
    }

    public function collect(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post((string) $this->config('collect_path'), $this->body($request, 'collect'));

        return $this->result($response, 'La demande de paiement Digitwace a echoue.');
    }

    public function payout(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post((string) $this->config('payout_path'), $this->body($request, 'payout'));

        return $this->result($response, 'Le decaissement Digitwace a echoue.');
    }

    public function status(string $reference, ?string $providerReference, string $type = 'collect'): GatewayResult
    {
        $this->ensureConfigured();

        $path = str_replace(['{reference}', '{id}'], [$reference, $providerReference ?? $reference], (string) $this->config('status_path'));
        $response = $this->http()->get($path);

        if ($response->failed()) {
            $this->fail($response, 'Impossible de recuperer le statut Digitwace.');
        }

        $json = $response->json() ?? [];
        $status = GatewayResult::mapStatus($this->pick($json, ['status', 'data.status', 'transaction.status']));

        return new GatewayResult($status, $this->pick($json, ['transaction_id', 'transactionId', 'data.transaction_id', 'data.id']) ?? $providerReference, $this->pick($json, ['message', 'data.message']), $json, $reference);
    }

    public function handleWebhook(Request $request): ?GatewayResult
    {
        if (! $this->webhookIsAuthentic($request)) {
            return null;
        }

        $data = $request->all();
        $reference = $this->pick($data, ['reference', 'external_reference', 'externalReference', 'merchant_reference', 'data.reference', 'data.external_reference']);

        if (! $reference) {
            return null;
        }

        return new GatewayResult(
            GatewayResult::mapStatus($this->pick($data, ['status', 'data.status', 'transaction.status'])),
            $this->pick($data, ['transaction_id', 'transactionId', 'data.transaction_id', 'id']),
            $this->pick($data, ['message', 'data.message']),
            $data,
            (string) $reference,
        );
    }

    protected function body(PaymentRequest $request, string $type): array
    {
        return [
            'type' => $type,
            'reference' => $request->reference,
            'external_reference' => $request->reference,
            'amount' => $request->integerAmount(),
            'currency' => $request->currency,
            'country' => $request->country,
            'phone' => $request->phone,
            'phone_number' => $request->phone,
            'operator' => strtoupper((string) $request->network),
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'description' => $request->description,
            'callback_url' => $request->callbackUrl,
        ];
    }

    protected function result($response, string $fallback): GatewayResult
    {
        if ($response->failed()) {
            $this->fail($response, $fallback);
        }

        $json = $response->json() ?? [];
        $providerRef = $this->pick($json, ['transaction_id', 'transactionId', 'data.transaction_id', 'data.transactionId', 'data.id', 'id']);
        $status = GatewayResult::mapStatus($this->pick($json, ['status', 'data.status']));

        // A l'initiation, un "success" signifie presque toujours "demande
        // acceptee" : l'argent n'est considere comme recu qu'apres le
        // webhook ou la verification de statut.
        return $status === GatewayResult::FAILED
            ? GatewayResult::failed($this->pick($json, ['message', 'data.message']) ?? $fallback, $json)
            : GatewayResult::pending($providerRef ? (string) $providerRef : null, $json);
    }

    protected function http()
    {
        $headers = ['X-API-KEY' => (string) $this->config('api_key')];

        if (filled($this->config('api_secret'))) {
            $headers['X-API-SECRET'] = (string) $this->config('api_secret');
        }

        return Http::baseUrl(rtrim((string) $this->config('base_url'), '/'))
            ->withToken((string) $this->config('api_key'))
            ->withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->timeout((int) $this->config('timeout', 25));
    }
}
