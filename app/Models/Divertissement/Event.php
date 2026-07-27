<?php

namespace App\Models\Divertissement;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// DIVERTISSEMENT module (skeleton) - billetterie evenements.
class Event extends Model
{
    use HasFactory, HasPublishingStatus;

    protected $fillable = [
        'organizer_id', 'title', 'slug', 'category', 'description', 'venue',
        'city', 'country', 'starts_at', 'ends_at', 'price', 'currency',
        'capacity', 'image', 'is_active', 'status', 'rejection_reason',
    ];

    protected $appends = ['image_url'];

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

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
