<?php

namespace App\Models\Flights;

/**
 * Cycle de vie d'un dossier de vol (cahier des charges, section 11).
 * Les transitions autorisees sont definies ICI et appliquees cote serveur
 * par FlightStateMachine.
 */
final class FlightStatus
{
    public const DRAFT = 'DRAFT';
    public const SEARCHED = 'SEARCHED';
    public const PRICE_CONFIRMED = 'PRICE_CONFIRMED';
    public const PAYMENT_PENDING = 'PAYMENT_PENDING';
    public const PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';
    public const BOOKING_PENDING = 'BOOKING_PENDING';
    public const BOOKED = 'BOOKED';
    public const TICKETING_PENDING = 'TICKETING_PENDING';
    public const TICKETED = 'TICKETED';
    public const FAILED = 'FAILED';
    public const REFUND_PENDING = 'REFUND_PENDING';
    public const REFUNDED = 'REFUNDED';
    public const CANCELLED = 'CANCELLED';

    /** @var array<string, string[]> */
    public const TRANSITIONS = [
        self::DRAFT => [self::SEARCHED, self::PRICE_CONFIRMED, self::CANCELLED],
        self::SEARCHED => [self::PRICE_CONFIRMED, self::CANCELLED],
        self::PRICE_CONFIRMED => [self::PAYMENT_PENDING, self::CANCELLED],
        // Retour a PRICE_CONFIRMED si le paiement est refuse / abandonne (nouvel essai).
        self::PAYMENT_PENDING => [self::PAYMENT_CONFIRMED, self::PRICE_CONFIRMED, self::CANCELLED],
        self::PAYMENT_CONFIRMED => [self::BOOKING_PENDING, self::FAILED, self::REFUND_PENDING],
        self::BOOKING_PENDING => [self::BOOKED, self::FAILED],
        self::BOOKED => [self::TICKETING_PENDING, self::FAILED, self::CANCELLED, self::REFUND_PENDING],
        self::TICKETING_PENDING => [self::TICKETED, self::FAILED],
        self::TICKETED => [self::REFUND_PENDING],
        // Un dossier en echec est repris (relance controlee) ou rembourse.
        self::FAILED => [self::BOOKING_PENDING, self::TICKETING_PENDING, self::REFUND_PENDING, self::CANCELLED],
        self::REFUND_PENDING => [self::REFUNDED, self::FAILED],
        self::REFUNDED => [],
        self::CANCELLED => [self::REFUND_PENDING],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** Libelle client : ne promet jamais un billet avant TICKETED. */
    public static function label(string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Brouillon',
            self::SEARCHED => 'Recherche effectuee',
            self::PRICE_CONFIRMED => 'Prix confirme - en attente de paiement',
            self::PAYMENT_PENDING => 'Paiement en attente de confirmation',
            self::PAYMENT_CONFIRMED => 'Paiement recu - reservation en cours',
            self::BOOKING_PENDING => 'Reservation en cours',
            self::BOOKED => 'Reservation confirmee - billet en cours d\'emission',
            self::TICKETING_PENDING => 'Emission du billet en cours',
            self::TICKETED => 'Billet emis',
            self::FAILED => 'Traitement en cours de verification par notre equipe',
            self::REFUND_PENDING => 'Remboursement en cours',
            self::REFUNDED => 'Rembourse',
            self::CANCELLED => 'Annule',
            default => $status,
        };
    }

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
