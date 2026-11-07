<?php

namespace App\Models\Fret;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// FRET module - a carrier/partner's published freight service (route,
// mode, price per kg). Not to be confused with Shipment, which tracks an
// actual customer shipment once created.
class FreightOffer extends Model
{
    use HasFactory, HasPublishingStatus;

    protected $fillable = [
        'carrier_id', 'title', 'slug', 'description', 'mode',
        'origin_city', 'origin_country', 'destination_city', 'destination_country',
        'price_per_kg', 'currency', 'capacity_kg', 'image', 'is_active',
        'status', 'rejection_reason',
        'service_type', 'pricing_mode', 'price_is_firm', 'unit', 'min_price', 'valid_until',
        'transit_days_min', 'transit_days_max', 'inclusions', 'exclusions', 'restrictions',
        'promo_percent', 'promo_until',
    ];

    /** Champs sensibles : leur modification retire l'offre de la publication jusqu'a nouvelle validation. */
    public const SENSITIVE = [
        'mode', 'origin_city', 'origin_country', 'destination_city', 'destination_country',
        'price_per_kg', 'unit', 'min_price', 'currency', 'pricing_mode', 'price_is_firm', 'inclusions', 'exclusions', 'restrictions',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
            'price_is_firm' => 'boolean',
            'min_price' => 'decimal:2',
            'promo_percent' => 'decimal:2',
            'valid_until' => 'date',
            'promo_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $offer) {
            $offer->slug ??= Str::slug($offer->title).'-'.Str::random(6);
        });
    }

    public function carrier()
    {
        return $this->belongsTo(User::class, 'carrier_id');
    }

    public function partnerProfile()
    {
        return $this->hasOne(FreightPartnerProfile::class, 'user_id', 'carrier_id');
    }

    /** Visible publiquement : publiee, active, non expiree et partenaire approuve. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('carrier_id')
                ->orWhereHas('partnerProfile', fn ($p) => $p->where('status', 'approved')));
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
