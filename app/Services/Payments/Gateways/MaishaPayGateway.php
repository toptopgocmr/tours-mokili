<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * MaishaPay Web API v2.1 - https://docs.maishapay.net/front
 * Base : https://marchand.maishapay.online/api
 *
 *  - Collecte Mobile Money : POST /collect/v2/store/mobileMoney
 *      { transactionReference, gatewayMode ("1" live | "0" sandbox),
 *        publicApiKey, secretApiKey,
 *        order { amount, currency, customerFullName, customerEmailAdress },
 *        paymentChannel { channel: "MOBILEMONEY", provider, walletID, callbackUrl } }
 *  - Statut : /transaction/rest/v2/check  { gatewayMode, publicApiKey, secretApiKey, transactionId }
 *  - Decaissement (B2C) : POST /b2c/transfer
 *      { amount, currency, recipient_phone, operator, reference, description, callback_url }
 *  - Webhook : en-tete X-MaishaPay-Signature = "sha256=" + HMAC-SHA256(corps brut, secret webhook)
 *      { type: transaction.success|failed|..., data { reference, payment_status, transaction_id } }
 *
 * La doc publique melange plusieurs formats (snake_case / camelCase,
 * montant en centimes ou non) : les points incertains sont reglables par
 * variables d'environnement (MAISHAPAY_AMOUNT_MULTIPLIER, MAISHAPAY_XAF_CODE,
 * MAISHAPAY_*_PATH) pour s'aligner sans toucher au code. Faire un premier
 * paiement test de petit montant avant l'ouverture au public.
 */
class MaishaPayGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'maishapay';
    }

    public function isConfigured(): bool
    {
        return filled($this->config('public_key')) && filled($this->config('secret_key'));
    }

    public function collect(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post($this->url('collect_path'), [
            'transactionReference' => $request->reference,
            'gatewayMode' => (string) $this->mode(),
            'publicApiKey' => $this->config('public_key'),
            'secretApiKey' => $this->config('secret_key'),
            'order' => [
                'amount' => (string) $this->amount($request),
                'currency' => $this->currency($request->currency),
                'customerFullName' => $request->customerName,
                'customerEmailAdress' => $request->customerEmail,
            ],
            'paymentChannel' => [
                'channel' => 'MOBILEMONEY',
                'provider' => $this->provider($request),
                'walletID' => '+'.$request->phone,
                'callbackUrl' => $request->callbackUrl,
            ],
        ]);

        return $this->result($response, 'La demande de paiement MaishaPay a echoue.');
    }

    public function payout(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = $this->http()->post($this->url('payout_path'), [
            'amount' => $this->amount($request),
            'currency' => $this->currency($request->currency),
            'recipient_phone' => '+'.$request->phone,
            'operator' => strtolower($this->provider($request)),
            'reference' => $request->reference,
            'description' => $request->description,
            'callback_url' => $request->callbackUrl,
            // Certaines routes MaishaPay attendent aussi les cles dans le corps.
            'gatewayMode' => (string) $this->mode(),
            'publicApiKey' => $this->config('public_key'),
            'secretApiKey' => $this->config('secret_key'),
        ]);

        return $this->result($response, 'Le transfert MaishaPay a echoue.');
    }

    public function status(string $reference, ?string $providerReference, string $type = 'collect'): GatewayResult
    {
        $this->ensureConfigured();

        // Sans identifiant MaishaPay, on interroge par notre reference (?useRef=1).
        $useRef = $providerReference === null;
        $url = $this->url('status_path').($useRef ? '?useRef=1' : '');
        $body = [
            'gatewayMode' => (string) $this->mode(),
            'publicApiKey' => $this->config('public_key'),
            'secretApiKey' => $this->config('secret_key'),
            'transactionId' => $providerReference ?? $reference,
        ];

        $response = $this->http()->send('POST', $url, ['json' => $body]);
        if ($response->status() === 405) {
            $response = $this->http()->send('GET', $url, ['json' => $body]);
        }

        if ($response->failed()) {
            $this->fail($response, 'Impossible de recuperer le statut MaishaPay.');
        }

        $json = $response->json() ?? [];
        $raw = $this->pick($json, ['data.payment_status', 'data.transactionStatus', 'data.status', 'payment_status', 'transactionStatus', 'status']);
        $status = GatewayResult::mapStatus(is_string($raw) ? $raw : null);

        return new GatewayResult($status, $providerReference ?? $this->pick($json, ['data.transaction_id', 'data.transactionId']), null, $json, $reference);
    }

    public function handleWebhook(Request $request): ?GatewayResult
    {
        if (! $this->signatureIsValid($request)) {
            return null;
        }

        $data = $request->all();
        $reference = $this->pick($data, [
            'data.reference', 'reference', 'transactionReference', 'originatingTransactionId',
            'data.originatingTransactionId', 'transactionRefId',
        ]);

        if (! $reference) {
            return null;
        }

        $type = (string) ($data['type'] ?? '');
        $rawStatus = $this->pick($data, ['data.payment_status', 'payment_status', 'data.status', 'transactionStatus', 'description', 'status']);
        $status = match (true) {
            str_ends_with($type, '.success') => GatewayResult::SUCCESS,
            str_ends_with($type, '.failed'), str_ends_with($type, '.cancelled') => GatewayResult::FAILED,
            (string) ($data['status'] ?? '') === '200' && strtoupper((string) ($data['description'] ?? '')) === 'APPROVED' => GatewayResult::SUCCESS,
            default => GatewayResult::mapStatus(is_string($rawStatus) ? $rawStatus : null),
        };

        $providerRef = $this->pick($data, ['data.transaction_id', 'transaction_id', 'operatorRefId']);

        // Sans secret de signature, on ne croit pas le callback sur parole :
        // on redemande le statut a MaishaPay avec nos cles.
        if ((string) $this->config('webhook_secret') === '') {
            try {
                $confirmed = $this->status((string) $reference, null, str_starts_with($type, 'b2c.') ? 'payout' : 'collect');

                return new GatewayResult($confirmed->status, $providerRef ?? $confirmed->providerReference, null, $data, (string) $reference);
            } catch (PaymentGatewayException) {
                return new GatewayResult(GatewayResult::PENDING, $providerRef, null, $data, (string) $reference);
            }
        }

        return new GatewayResult(
            $status,
            $providerRef,
            $this->pick($data, ['data.message', 'message', 'description']),
            $data,
            (string) $reference,
        );
    }

    /** Signature "sha256=<hmac>" (en-tete X-MaishaPay-Signature) si un secret webhook est configure. */
    protected function signatureIsValid(Request $request): bool
    {
        $secret = (string) $this->config('webhook_secret');
        if ($secret === '') {
            return true; // le statut est re-verifie via l'API (voir PaymentService)
        }

        $header = (string) $request->header('X-MaishaPay-Signature');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return $header !== '' && hash_equals($expected, $header);
    }

    protected function provider(PaymentRequest $request): string
    {
        $provider = $this->networkCode($request->country, $request->network) ?? strtoupper((string) $request->network);

        if ($provider === '') {
            throw new PaymentGatewayException('Choisissez votre operateur Mobile Money pour payer avec MaishaPay.');
        }

        return $provider;
    }

    protected function amount(PaymentRequest $request): int
    {
        return (int) round($request->amount * (float) $this->config('amount_multiplier', 1));
    }

    protected function currency(string $currency): string
    {
        $currency = strtoupper($currency);

        return $currency === 'XAF' ? (string) $this->config('xaf_code', 'XAF') : $currency;
    }

    protected function mode(): int
    {
        return (int) $this->config('mode', 0);
    }

    protected function url(string $pathKey): string
    {
        return rtrim((string) $this->config('base_url'), '/').'/'.ltrim((string) $this->config($pathKey), '/');
    }

    protected function result($response, string $fallback): GatewayResult
    {
        $json = $response->json() ?? [];

        if ($response->failed()) {
            $this->fail($response, $fallback);
        }

        $data = $json['data'] ?? $json['original']['data'] ?? $json;
        $transactionId = $this->pick(is_array($data) ? $data : [], ['transaction_id', 'transactionId']);
        $raw = $this->pick(is_array($data) ? $data : [], ['payment_status', 'transactionStatus', 'status']);

        if (($json['status'] ?? null) === 'error' || GatewayResult::mapStatus(is_string($raw) ? $raw : null) === GatewayResult::FAILED) {
            return GatewayResult::failed($this->pick($json, ['message', 'data.message', 'data.transactionDescription']) ?? $fallback, $json, $transactionId ? (string) $transactionId : null);
        }

        // Demande envoyee au telephone : confirmation USSD du client attendue,
        // le statut final arrive par callback ou verification de statut.
        return GatewayResult::pending($transactionId ? (string) $transactionId : null, $json);
    }

    protected function http()
    {
        return Http::withToken((string) $this->config('secret_key'))
            ->withHeaders(['X-MaishaPay-Version' => '2024-01-01'])
            ->acceptJson()
            ->asJson()
            ->timeout((int) $this->config('timeout', 25));
    }
}
