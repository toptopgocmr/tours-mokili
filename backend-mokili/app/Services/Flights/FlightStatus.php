<?php

namespace App\Services\Flights;

/**
 * Statuts du dossier de billetterie (cahier des charges, section 11) et
 * transitions autorisees. Toute transition passe par FlightStateMachine,
 * cote serveur, et est historisee.
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

    public const LABELS = [
        self::DRAFT => 'Dossier commence',
        self::SEARCHED => 'Offre selectionnee',
        self::PRICE_CONFIRMED => 'Prix confirme',
        self::PAYMENT_PENDING => 'Paiement en attente',
        self::PAYMENT_CONFIRMED => 'Paiement confirme',
        self::BOOKING_PENDING => 'Reservation en cours',
        self::BOOKED => 'Reservation confirmee',
        self::TICKETING_PENDING => 'Emission du billet en cours',
        self::TICKETED => 'Billet emis',
        self::FAILED => 'Echec - traitement en cours',
        self::REFUND_PENDING => 'Remboursement en cours',
        self::REFUNDED => 'Rembourse',
        self::CANCELLED => 'Annule',
    ];

    public const TRANSITIONS = [
        self::DRAFT => [self::SEARCHED, self::CANCELLED],
        self::SEARCHED => [self::PRICE_CONFIRMED, self::PAYMENT_CONFIRMED, self::CANCELLED],
        self::PRICE_CONFIRMED => [self::PAYMENT_PENDING, self::PAYMENT_CONFIRMED, self::SEARCHED, self::CANCELLED],
        self::PAYMENT_PENDING => [self::PAYMENT_CONFIRMED, self::PRICE_CONFIRMED, self::SEARCHED, self::CANCELLED],
        self::PAYMENT_CONFIRMED => [self::BOOKING_PENDING, self::FAILED, self::REFUND_PENDING],
        self::BOOKING_PENDING => [self::BOOKED, self::FAILED],
        self::BOOKED => [self::TICKETING_PENDING, self::TICKETED, self::FAILED, self::CANCELLED],
        self::TICKETING_PENDING => [self::TICKETED, self::FAILED],
        self::TICKETED => [self::REFUND_PENDING, self::CANCELLED],
        // Reprise controlee depuis l'echec (apres verification fournisseur)
        // ou compensation / remboursement.
        self::FAILED => [self::BOOKING_PENDING, self::BOOKED, self::TICKETING_PENDING, self::TICKETED, self::REFUND_PENDING, self::CANCELLED],
        self::REFUND_PENDING => [self::REFUNDED, self::TICKETED, self::FAILED, self::CANCELLED],
        self::REFUNDED => [],
        self::CANCELLED => [self::REFUND_PENDING, self::PAYMENT_CONFIRMED],
    ];

    /** Statuts ou l'argent du client a ete encaisse. */
    public const PAID = [
        self::PAYMENT_CONFIRMED, self::BOOKING_PENDING, self::BOOKED, self::TICKETING_PENDING,
        self::TICKETED, self::REFUND_PENDING,
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }
}
