<?php

namespace App\Models\Fret;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// FRET module - a carrier/partner's published freight service (route,
// mode, price per kg). Not to be confused with Shipment, which tracks an
// actual customer shipment once created.
class FreightOffer extends Model
{
    protected $fillable = [
        'carrier_id', 'title', 'slug', 'description', 'mode',
        'origin_city', 'origin_country', 'destination_city', 'destination_country',
        'price_per_kg', 'currency', 'capacity_kg', 'image', 'is_active',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
