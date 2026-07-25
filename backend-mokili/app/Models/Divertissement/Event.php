<?php

namespace App\Models\Divertissement;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// DIVERTISSEMENT module (skeleton) - billetterie evenements.
class Event extends Model
{
    protected $fillable = [
        'organizer_id', 'title', 'slug', 'category', 'description', 'venue',
        'city', 'country', 'starts_at', 'ends_at', 'price', 'currency',
        'capacity', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->slug ??= Str::slug($event->title).'-'.Str::random(6);
        });
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
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
