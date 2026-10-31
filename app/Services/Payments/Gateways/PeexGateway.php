<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentRequest;
use App\Services\Peex\PeexClient;
use App\Services\Peex\PeexException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Peex - https://peex-api-docs.peexit.com
 *
 * Reutilise App\Services\Peex\PeexClient (cle SECRETKEY, sandbox/prod)
 * deja en place : verifyWallet() avant la collecte, puis
 * clients/request_payment. En sandbox Peex fixe tous les montants a 10 FCFA.
 *
 * Peex ne documente pas d'endpoint de statut public : le statut final
 * arrive par callback (track_id + status) ou est confirme a la main depuis
 * le back-office.
 */
class PeexGateway extends AbstractGateway
{
    public function __construct(protected PeexClient $client) {}

    public function name(): string
    {
        return 'peex';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.peex.secret_key'));
    }

    public function supportsStatusCheck(): bool
    {
        return false;
    }

    public function collect(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        try {
            // Peex exige un portefeuille verifie avant toute demande.
            $wallet = $this->client->verifyWallet($request->country, $request->phone);

            if (! $wallet['isValid']) {
                return GatewayResult::failed('Ce numero ne peut pas payer via Peex (portefeuille non valide).', $wallet);
            }

            $response = $this->client->requestPayment([
                'countryCode' => $request->country,
                'accountNumber' => $request->phone,
                'amount' => $request->integerAmount(),
                'currency' => $request->currency,
                'track_id' => $request->reference,
                'description' => $request->description,
                'callback_url' => $request->callbackUrl,
            ]);
        } catch (PeexException $e) {
            throw new PaymentGatewayException($e->getMessage(), [], (int) $e->statusCode);
        }

        return $this->fromPeexResponse($response);
    }

    public function payout(PaymentRequest $request): GatewayResult
    {
        $this->ensureConfigured();

        $response = Http::baseUrl((string) config('services.peex.base_url'))
            ->withHeaders(['SECRETKEY' => (string) config('services.peex.secret_key')])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.peex.timeout', 15))
            ->post((string) $this->config('payout_path', 'clients/request_payment'), [
                'type' => 'disbursement',
                'countryCode' => $request->country,
                'accountNumber' => $request->phone,
                'amount' => $request->integerAmount(),
                'currency' => $request->currency,
                'track_id' => $request->reference,
                'beneficiary_name' => $request->customerName,
                'description' => $request->description,
                'callback_url' => $request->callbackUrl,
            ]);

        if ($response->failed()) {
            $this->fail($response, 'Le decaissement Peex a echoue.');
        }

        return $this->fromPeexResponse($response->json() ?? []);
    }

    public function status(string $reference, ?string $providerReference, string $type = 'collect'): GatewayResult
    {
        return $this->unknown($reference);
    }

    public function handleWebhook(Request $request): ?GatewayResult
    {
        if (! $this->webhookIsAuthentic($request)) {
            return null;
        }

        $data = $request->all();
        $reference = $this->pick($data, ['track_id', 'trackId', 'request.track_id', 'data.track_id', 'reference']);

        if (! $reference) {
            return null;
        }

        $status = GatewayResult::mapStatus($this->pick($data, ['status', 'request.status', 'data.status', 'transaction_status']));

        return new GatewayResult(
            $status,
            $this->pick($data, ['request.id', 'id', 'data.id', 'transaction_id']),
            $this->pick($data, ['message', 'reason', 'data.message']),
            $data,
            (string) $reference,
        );
    }

    protected function fromPeexResponse(array $response): GatewayResult
    {
        $providerRef = $this->pick($response, ['request.id', 'request.track_id', 'id', 'data.id']);
        $rawStatus = $this->pick($response, ['request.status', 'status', 'data.status']);

        return match (GatewayResult::mapStatus(is_string($rawStatus) ? $rawStatus : null)) {
            GatewayResult::SUCCESS => GatewayResult::success($providerRef ? (string) $providerRef : null, $response),
            GatewayResult::FAILED => GatewayResult::failed($this->pick($response, ['message', 'request.message']) ?? 'Paiement Peex refuse.', $response),
            default => GatewayResult::pending($providerRef ? (string) $providerRef : null, $response),
        };
    }
}
