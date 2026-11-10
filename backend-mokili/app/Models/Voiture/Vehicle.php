<?php

namespace App\Models\Voiture;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// VOITURE module (skeleton).
class Vehicle extends Model
{
    use HasPublishingStatus;

    protected $fillable = [
        'owner_id', 'title', 'offer_type', 'slug', 'brand', 'model', 'description', 'year', 'category',
        'transmission', 'seats', 'mileage_km', 'fuel', 'services', 'price_per_day', 'sale_price',
        'currency', 'city', 'country', 'phone',
        'image', 'is_active', 'status', 'rejection_reason', 'discount_percent', 'promo_label', 'promo_ends_at',
    ];

    protected $appends = ['image_url', 'offer_type_label', 'display_price', 'promo'];

    public function getOfferTypeLabelAttribute(): string
    {
        return config('catalogue.voiture.offer_types')[$this->offer_type ?? 'location'] ?? (string) $this->offer_type;
    }

    /** Prix affiche selon le type : / jour (location), prix de vente, ou "a partir de" (mecanique). */
    public function getDisplayPriceAttribute(): ?array
    {
        $price = match ($this->offer_type) {
            'vente' => $this->sale_price !== null ? ['amount' => (float) $this->sale_price, 'suffix' => ''] : null,
            'mecanique' => $this->sale_price !== null ? ['amount' => (float) $this->sale_price, 'suffix' => 'a partir de'] : null,
            default => $this->price_per_day !== null ? ['amount' => (float) $this->price_per_day, 'suffix' => '/ jour'] : null,
        };
        // Promotion en cours : prix remise + prix d'origine barre.
        if ($price && $this->discount_percent > 0) {
            $price['original'] = $price['amount'];
            $price['amount'] = round($price['amount'] * (1 - $this->discount_percent / 100), 2);
        }

        return $price;
    }

    /** Une promotion terminee (promo_ends_at passe) ne s'applique plus. */
    public function getDiscountPercentAttribute($value): int
    {
        $end = $this->attributes['promo_ends_at'] ?? null;

        return $end && \Carbon\Carbon::parse($end)->isPast() ? 0 : (int) $value;
    }

    /** Resume de la promotion en cours (null si aucune). */
    public function getPromoAttribute(): ?array
    {
        return $this->discount_percent > 0 ? [
            'percent' => $this->discount_percent,
            'label' => $this->attributes['promo_label'] ?? null,
            'ends_at' => isset($this->attributes['promo_ends_at']) && $this->attributes['promo_ends_at'] ? \Carbon\Carbon::parse($this->attributes['promo_ends_at'])->toIso8601String() : null,
        ] : null;
    }

    /** Prix journalier de location apres promotion (reservation). */
    public function discountedDayPrice(): float
    {
        return round((float) $this->price_per_day * (1 - $this->discount_percent / 100), 2);
    }

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'services' => 'array',
            'is_active' => 'boolean',
            'promo_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vehicle) {
            $vehicle->slug ??= Str::slug($vehicle->title).'-'.Str::random(6);
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Demo listings store a full external photo URL directly; real
        // partner uploads store a Storage-disk-relative path.
        return str_starts_with($this->image, 'http')
            ? $this->image
            : Storage::disk('public')->url($this->image);
    }
}
