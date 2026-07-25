<?php

namespace App\Models\Voiture;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// VOITURE module (skeleton).
class Vehicle extends Model
{
    protected $fillable = [
        'owner_id', 'title', 'slug', 'brand', 'model', 'year', 'category',
        'transmission', 'seats', 'price_per_day', 'currency', 'city', 'country',
        'image', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
