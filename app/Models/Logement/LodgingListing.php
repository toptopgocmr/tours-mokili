<?php

namespace App\Models\Logement;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// LOGEMENT module (skeleton, ready for full CRUD following the
// Voyage\TravelOffer pattern).
class LodgingListing extends Model
{
    use HasFactory, HasPublishingStatus;

    protected $fillable = [
        'owner_id', 'title', 'slug', 'description', 'city', 'country', 'address',
        'price_per_night', 'currency', 'bedrooms', 'bathrooms', 'max_guests',
        'amenities', 'image', 'is_active', 'status', 'rejection_reason',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_per_night' => 'decimal:2',
            'is_active' => 'boolean',
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
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
