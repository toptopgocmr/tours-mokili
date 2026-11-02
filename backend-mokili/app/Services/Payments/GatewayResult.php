<?php

namespace App\Services\Payments;

/**
 * Reponse normalisee d'une passerelle : un des 3 statuts internes, la
 * reference cote agregateur, un message lisible et la reponse brute
 * (journalisee dans payments.gateway_response / payouts.gateway_response).
 */
final class GatewayResult
{
    public const PENDING = 'pending';   // accepte, en attente de la confirmation du client / de l'operateur
    public const SUCCESS = 'success';   // argent effectivement debite / credite
    public const FAILED = 'failed';     // refuse, annule, solde insuffisant...

    public function __construct(
        public readonly string $status,
        public readonly ?string $providerReference = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
        public readonly ?string $ourReference = null, // reference renvoyee par un webhook
    ) {}

    public static function pending(?string $providerRef, array $raw = [], ?string $message = null): self
    {
        return new self(self::PENDING, $providerRef, $message ?? 'Confirmez le paiement sur votre telephone.', $raw);
    }

    public static function success(?string $providerRef, array $raw = [], ?string $message = null): self
    {
        return new self(self::SUCCESS, $providerRef, $message ?? 'Paiement confirme.', $raw);
    }

    public static function failed(?string $message, array $raw = [], ?string $providerRef = null): self
    {
        return new self(self::FAILED, $providerRef, $message ?? 'Le paiement a echoue.', $raw);
    }

    public function isFinal(): bool
    {
        return $this->status !== self::PENDING;
    }

    /** Traduit un statut texte d'agregateur vers nos 3 statuts. */
    public static function mapStatus(?string $status): string
    {
        $s = strtoupper((string) $status);

        return match (true) {
            in_array($s, ['COMPLETED', 'SUCCESS', 'SUCCESSFUL', 'SUCCEEDED', 'PAID', 'APPROVED', 'DONE', '200', 'OK', 'VALIDATED'], true) => self::SUCCESS,
            in_array($s, ['FAILED', 'FAILURE', 'REJECTED', 'CANCELLED', 'CANCELED', 'EXPIRED', 'DECLINED', 'ERROR', 'REFUSED'], true) => self::FAILED,
            default => self::PENDING,
        };
    }
}
