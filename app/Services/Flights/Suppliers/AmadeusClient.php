<?php

namespace App\Services\Flights\Suppliers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Client HTTP Amadeus Self-Service : OAuth2 client_credentials + gestion d'erreurs typee. */
class AmadeusClient
{
    public function configured(): bool
    {
        return filled(config('flights.amadeus.client_id')) && filled(config('flights.amadeus.client_secret'));
    }

    /**
     * @param  array{json?:array, query?:array}  $options
     *
     * @throws SupplierTimeoutException  resultat inconnu (reseau, 5xx, 429)
     * @throws SupplierRejectedException refus definitif (4xx) ; ->getCode() = statut HTTP
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $response = $this->send($method, $path, $options, $this->token());

        if ($response->status() === 401) { // jeton expire : un seul renouvellement
            Cache::forget('flights.amadeus.token');
            $response = $this->send($method, $path, $options, $this->token());
        }

        return $this->handle($response);
    }

    private function send(string $method, string $path, array $options, string $token): Response
    {
        try {
            return $this->http()->withToken($token)->send(strtoupper($method), ltrim($path, '/'), array_filter([
                'json' => $options['json'] ?? null,
                'query' => $options['query'] ?? null,
            ]));
        } catch (ConnectionException $e) {
            throw new SupplierTimeoutException('Amadeus injoignable ou delai depasse.', 0, $e);
        }
    }

    private function handle(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status = $response->status();
        $detail = data_get($response->json(), 'errors.0.detail')
            ?? data_get($response->json(), 'errors.0.title')
            ?? 'Erreur Amadeus';

        if ($status >= 500 || $status === 429) {
            throw new SupplierTimeoutException("Amadeus indisponible ({$status}).", $status);
        }

        throw new SupplierRejectedException($detail, $status);
    }

    private function token(): string
    {
        return Cache::remember('flights.amadeus.token', 25 * 60, function () {
            if (! $this->configured()) {
                throw new SupplierException('Cles Amadeus non configurees (AMADEUS_CLIENT_ID / AMADEUS_CLIENT_SECRET).');
            }

            try {
                $res = $this->http()->asForm()->post('v1/security/oauth2/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('flights.amadeus.client_id'),
                    'client_secret' => config('flights.amadeus.client_secret'),
                ]);
            } catch (ConnectionException $e) {
                throw new SupplierTimeoutException('Authentification Amadeus impossible.', 0, $e);
            }

            if (! $res->successful() || ! $res->json('access_token')) {
                throw new SupplierException('Authentification Amadeus refusee ('.$res->status().').');
            }

            return $res->json('access_token');
        });
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('flights.amadeus.base_url'), '/').'/')
            ->acceptJson()
            ->timeout((int) config('flights.amadeus.timeout', 25));
    }
}
