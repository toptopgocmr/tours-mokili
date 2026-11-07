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
        'image', 'is_active', 'status', 'rejection_reason',
    ];

    protected $appends = ['image_url', 'offer_type_label', 'display_price'];

    public function getOfferTypeLabelAttribute(): string
    {
        return config('catalogue.voiture.offer_types')[$this->offer_type ?? 'location'] ?? (string) $this->offer_type;
    }

    /** Prix affiche selon le type : / jour (location), prix de vente, ou "a partir de" (mecanique). */
    public function getDisplayPriceAttribute(): ?array
    {
        return match ($this->offer_type) {
            'vente' => $this->sale_price !== null ? ['amount' => (float) $this->sale_price, 'suffix' => ''] : null,
            'mecanique' => $this->sale_price !== null ? ['amount' => (float) $this->sale_price, 'suffix' => 'a partir de'] : null,
            default => $this->price_per_day !== null ? ['amount' => (float) $this->price_per_day, 'suffix' => '/ jour'] : null,
        };
    }

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'services' => 'array',
            'is_active' => 'boolean',
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
