<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Generic booking shared by every service module through the
 * bookable() polymorphic relation.
 */
class Booking extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'bookable_type', 'bookable_id',
        'quantity', 'unit_price', 'total_amount', 'currency',
        'starts_at', 'ends_at', 'status', 'meta', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'meta' => 'array',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->reference ??= 'MKT-'.strtoupper(Str::random(8));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
