<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

abstract class AbstractGateway implements PaymentGateway
{
    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $base = 'payments.gateways.'.$this->name();

        return config($key ? "{$base}.{$key}" : $base, $default);
    }

    /** Code operateur attendu par CET agregateur pour un pays + reseau. */
    protected function networkCode(string $country, ?string $network): ?string
    {
        if (! $network) {
            return null;
        }

        return config('payments.networks.'.strtoupper($country).'.'.strtoupper($network).'.'.$this->name());
    }

    protected function fail(Response $response, string $fallback): never
    {
        Log::warning('Payment gateway error', [
            'gateway' => $this->name(),
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 2000),
        ]);

        $json = $response->json() ?? [];
        $message = Arr::get($json, 'failureReason.failureMessage')
            ?? Arr::get($json, 'error.message')
            ?? Arr::get($json, 'message')
            ?? Arr::get($json, 'original.message')
            ?? $fallback;

        throw new PaymentGatewayException((string) $message, is_array($json) ? $json : [], $response->status());
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException(
                $this->config('label', $this->name()).' n\'est pas encore configure (cles API manquantes).'
            );
        }
    }

    /**
     * Verification optionnelle d'un secret partage sur les webhooks :
     * en-tete X-Webhook-Secret egal au secret, ou signature HMAC-SHA256 du
     * corps brut dans X-Signature / X-Webhook-Signature. Sans secret
     * configure, on accepte mais le statut est re-verifie aupres de l'API
     * quand c'est possible (voir PaymentService::handleWebhook).
     */
    protected function webhookIsAuthentic(Request $request): bool
    {
        $secret = $this->config('webhook_secret');

        if (! $secret) {
            return true;
        }

        if (hash_equals((string) $secret, (string) $request->header('X-Webhook-Secret'))) {
            return true;
        }

        $signature = (string) ($request->header('X-Signature') ?? $request->header('X-Webhook-Signature'));
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return $signature !== '' && (hash_equals($expected, $signature) || hash_equals($expected, strtolower($signature)));
    }

    /** Premier champ non vide parmi plusieurs chemins possibles (APIs heterogenes). */
    protected function pick(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = Arr::get($data, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function supportsStatusCheck(): bool
    {
        return true;
    }

    public function label(): string
    {
        return (string) $this->config('label', $this->name());
    }

    protected function unknown(string $reference): GatewayResult
    {
        return GatewayResult::pending(null, [], "Statut de {$reference} non encore disponible.");
    }
}
