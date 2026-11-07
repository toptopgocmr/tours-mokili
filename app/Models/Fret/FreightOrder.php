<?php

namespace App\Models\Fret;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Commande de fret. Le statut ne change que par FreightOrderService
 * (transitions controlees + journal d'audit). Paiement confirme <>
 * reservation confirmee par le partenaire <> expedition livree.
 */
class FreightOrder extends Model
{
    public const ACCEPTED = 'DEVIS_ACCEPTE';
    public const PAYMENT_PENDING = 'PAIEMENT_EN_ATTENTE';
    public const PAID = 'PAIEMENT_CONFIRME';
    public const CONFIRMED = 'RESERVATION_CONFIRMEE';
    public const CANCELLED = 'ANNULE';
    public const DISPUTE = 'LITIGE';

    public const TRANSITIONS = [
        self::ACCEPTED => [self::PAYMENT_PENDING, self::PAID, self::CANCELLED],
        self::PAYMENT_PENDING => [self::ACCEPTED, self::PAID, self::CANCELLED],
        self::PAID => [self::CONFIRMED, self::DISPUTE, self::CANCELLED],
        self::CONFIRMED => [self::DISPUTE, self::CANCELLED],
        self::DISPUTE => [self::CONFIRMED, self::CANCELLED],
        self::CANCELLED => [],
    ];

    public const LABELS = [
        self::ACCEPTED => 'Devis accepte - paiement a effectuer',
        self::PAYMENT_PENDING => 'Paiement en cours de verification',
        self::PAID => 'Paiement confirme - en attente de confirmation du partenaire',
        self::CONFIRMED => 'Reservation confirmee par le partenaire',
        self::CANCELLED => 'Annulee',
        self::DISPUTE => 'Litige en cours',
    ];

    protected $guarded = [];

    protected $appends = ['title', 'status_label'];

    protected function casts(): array
    {
        return [
            'frozen_terms' => 'array',
            'total_amount' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'partner_amount' => 'decimal:2',
            'accepted_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $o) => $o->reference ??= 'FRT-RES-'.strtoupper(Str::random(6)));
    }

    public function getTitleAttribute(): ?string
    {
        return $this->frozen_terms['title'] ?? $this->reference;
    }

    public function getStatusLabelAttribute(): ?string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function canTransition(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function offer()
    {
        return $this->belongsTo(FreightOffer::class, 'freight_offer_id');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'freight_order_id');
    }

    public function documents()
    {
        return $this->morphMany(FreightDocument::class, 'documentable');
    }
}
