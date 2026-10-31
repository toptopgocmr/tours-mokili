<?php

namespace App\Services\Payments;

/**
 * Demande de collecte (pay-in) ou de decaissement (pay-out), independante
 * de l'agregateur. Chaque passerelle traduit ces champs dans le format de
 * son API.
 */
final class PaymentRequest
{
    public function __construct(
        public readonly string $reference,      // notre reference unique (UUID pour pawaPay)
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $phone,          // MSISDN international sans "+" (ex: 242061234567)
        public readonly string $country,        // ISO2 : CG, CM, CD, GA...
        public readonly ?string $network = null, // MTN, AIRTEL, ORANGE, MPESA...
        public readonly ?string $customerName = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
    ) {}

    /** Montant entier (le XAF / XOF n'a pas de centimes). */
    public function integerAmount(): int
    {
        return (int) round($this->amount);
    }

    /** Numero au format local (sans indicatif pays). */
    public function localPhone(): string
    {
        $dial = (string) config("payments.dial_codes.{$this->country}", '');

        return $dial !== '' && str_starts_with($this->phone, $dial)
            ? substr($this->phone, strlen($dial))
            : $this->phone;
    }
}
