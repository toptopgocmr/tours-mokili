<?php

namespace App\Services\Flights\Data;

/**
 * Resultat d'une operation fournisseur (reservation, emission, statut).
 *
 * SUCCESS : operation confirmee par le fournisseur
 * PENDING : acceptee mais pas encore confirmee (traitement manuel, file...)
 * FAILED  : refus definitif
 * UNKNOWN : pas de reponse exploitable (timeout) - il faut interroger le
 *           fournisseur avant toute relance, jamais relancer a l'aveugle.
 */
final class SupplierResult
{
    public const SUCCESS = 'success';
    public const PENDING = 'pending';
    public const FAILED = 'failed';
    public const UNKNOWN = 'unknown';

    public function __construct(
        public readonly string $status,
        public readonly ?string $pnr = null,
        public readonly ?string $supplierReference = null,
        public readonly array $tickets = [],   // [passenger_id => ticket_number]
        public readonly ?string $message = null,
        public readonly array $raw = [],
        public readonly ?string $bookingStatus = null, // retrieve(): none|pending|booked|cancelled
        public readonly ?string $ticketStatus = null,  // retrieve(): none|pending|issued
    ) {}

    public static function success(?string $pnr = null, ?string $ref = null, array $tickets = [], array $raw = []): self
    {
        return new self(self::SUCCESS, $pnr, $ref, $tickets, null, $raw);
    }

    public static function pending(?string $message = null, array $raw = []): self
    {
        return new self(self::PENDING, message: $message, raw: $raw);
    }

    public static function failed(string $message, array $raw = []): self
    {
        return new self(self::FAILED, message: $message, raw: $raw);
    }

    public static function unknown(string $message, array $raw = []): self
    {
        return new self(self::UNKNOWN, message: $message, raw: $raw);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS;
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'pnr' => $this->pnr,
            'supplier_reference' => $this->supplierReference,
            'tickets' => $this->tickets ?: null,
            'message' => $this->message,
            'booking_status' => $this->bookingStatus,
            'ticket_status' => $this->ticketStatus,
        ], fn ($v) => $v !== null);
    }
}
