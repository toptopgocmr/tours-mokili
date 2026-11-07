<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

/** Journal inviolable : cree uniquement, jamais mis a jour ni supprime. */
class FlightAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
