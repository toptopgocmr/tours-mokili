<?php

namespace App\Services\Peex;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Peex Platform API.
 *
 * Docs: https://peex-api-docs.peexit.com
 *
 * Authentication: every request carries a `SECRETKEY` header
 * (see https://peex-api-docs.peexit.com/authentication).
 *
 * Currently used by MOKILI TOUR to verify a customer's mobile money /
 * bank wallet (`verifyWallet`) before allowing a booking checkout to
 * proceed to payment, across every service module (Voyage, Logement,
 * Voiture, Divertissement, Marketplace, Fret).
 */
class PeexClient
{
    public function __construct(
        protected readonly string $baseUrl,
        protected readonly ?string $secretKey,
        protected readonly int $timeout = 15,
    ) {}

    /**
     * POST clients/verify-wallet
     *
     * Verifies a mobile money or bank account before initiating a payment.
     *
     * @param  string  $countryCode  ISO country code, e.g. "CM", "GA".
     * @param  string  $accountNumber  Phone number or account identifier.
     * @return array{isValid: bool, accountName: ?string, operator: ?string, status: ?string}
     *
     * @throws PeexException
     */
    public function verifyWallet(string $countryCode, string $accountNumber): array
    {
        $response = $this->client()->post('clients/verify-wallet', [
            'countryCode' => strtoupper($countryCode),
            'accountNumber' => $accountNumber,
        ]);

        if ($response->failed()) {
            $this->throwFromResponse($response, 'Impossible de verifier ce portefeuille Peex.');
        }

        $data = $response->json();

        return [
            'isValid' => (bool) ($data['isValid'] ?? false),
            'accountName' => $data['accountName'] ?? null,
            'operator' => $data['operator'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }

    /**
     * GET clients/me - partner information (balance, fees).
     */
    public function partnerInfo(): array
    {
        $response = $this->client()->get('clients/me');

        if ($response->failed()) {
            $this->throwFromResponse($response, 'Impossible de recuperer les informations du compte Peex.');
        }

        return $response->json();
    }

    /**
     * POST clients/verify_phoneNumber - validate a phone number
     * ahead of a mobile money payment request.
     */
    public function verifyPhoneNumber(string $phoneNumber): array
    {
        $response = $this->client()->post('clients/verify_phoneNumber', [
            'phoneNumber' => $phoneNumber,
        ]);

        if ($response->failed()) {
            $this->throwFromResponse($response, 'Numero de telephone invalide.');
        }

        return $response->json();
    }

    /**
     * POST clients/request_payment - mobile money disbursement / collection
     * request, used once a booking/order has been confirmed and needs to be
     * settled through Peex.
     */
    public function requestPayment(array $payload): array
    {
        $response = $this->client()->post('clients/request_payment', $payload);

        if ($response->failed()) {
            $this->throwFromResponse($response, 'La demande de paiement Peex a echoue.');
        }

        return $response->json();
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'SECRETKEY' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->acceptJson();
    }

    protected function throwFromResponse($response, string $fallbackMessage): never
    {
        $error = $response->json('error') ?? [];

        Log::warning('Peex API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new PeexException(
            message: $error['message'] ?? $fallbackMessage,
            statusCode: $error['statusCode'] ?? $response->status(),
            errorName: $error['name'] ?? null,
        );
    }
}
