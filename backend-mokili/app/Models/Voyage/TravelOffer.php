<?php

namespace App\Models\Voyage;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * VOYAGE module - pilot module, fully implemented (CRUD + booking + checkout).
 */
class TravelOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'type', 'description', 'origin_city', 'origin_country',
        'destination_city', 'destination_country', 'airline', 'departure_at',
        'return_at', 'price', 'discount_percent', 'currency', 'seats_available',
        'image', 'is_featured', 'is_active',
    ];

    // Exposed on every JSON/Inertia response (web + API) so both
    // frontends can read the post-discount price directly.
    protected $appends = ['discounted_price'];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'return_at' => 'datetime',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TravelOffer $offer) {
            $offer->slug ??= Str::slug($offer->title).'-'.Str::random(6);
        });
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getDiscountedPriceAttribute(): float
    {
        return round($this->price * (1 - $this->discount_percent / 100), 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
