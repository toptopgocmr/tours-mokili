<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Candidature / profil d'un partenaire fret (valide par le staff avant toute publication). */
class FreightPartnerProfile extends Model
{
    public const PENDING = 'pending';
    public const NEEDS_CORRECTION = 'needs_correction';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const SUSPENDED = 'suspended';

    public const LABELS = [
        'pending' => 'En verification',
        'needs_correction' => 'Correction demandee',
        'approved' => 'Approuve',
        'rejected' => 'Refuse',
        'suspended' => 'Suspendu',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['zones' => 'array', 'modes' => 'array', 'reviewed_at' => 'datetime', 'commission_percent' => 'decimal:2'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->morphMany(FreightDocument::class, 'documentable');
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    /** Taux de commission : surcharge partenaire, sinon taux par defaut (a confirmer par contrat). */
    public function commissionRate(): float
    {
        return (float) ($this->commission_percent ?? config('freight.commission_percent', 8));
    }
}
