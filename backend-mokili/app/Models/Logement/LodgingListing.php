<?php

namespace App\Models\Logement;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// LOGEMENT module (skeleton, ready for full CRUD following the
// Voyage\TravelOffer pattern).
class LodgingListing extends Model
{
    use HasPublishingStatus;
    use \App\Models\Concerns\HasPromotion;

    protected $fillable = [
        'owner_id', 'title', 'property_type', 'slug', 'description', 'city', 'country', 'address',
        'price_per_night', 'currency', 'bedrooms', 'bathrooms', 'max_guests',
        'amenities', 'image', 'is_active', 'status', 'rejection_reason', 'discount_percent', 'promo_label', 'promo_ends_at',
    ];

    protected $appends = ['image_url', 'property_type_label', 'promo', 'final_price_per_night'];

    /** Prix par nuit apres promotion. */
    public function getFinalPricePerNightAttribute(): ?float
    {
        return $this->promoPrice($this->price_per_night);
    }

    public function getPropertyTypeLabelAttribute(): ?string
    {
        return $this->property_type ? (config('catalogue.logement.property_types')[$this->property_type] ?? $this->property_type) : null;
    }

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_per_night' => 'decimal:2',
            'is_active' => 'boolean',
            'promo_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $listing) {
            $listing->slug ??= Str::slug($listing->title).'-'.Str::random(6);
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
