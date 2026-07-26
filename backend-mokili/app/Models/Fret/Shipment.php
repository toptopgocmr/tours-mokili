<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// FRET module (skeleton) - suivi d'expedition, pas de "booking"
// classique: le cycle de vie est piloté par le champ status.
class Shipment extends Model
{
    protected $table = 'freight_shipments';

    protected $fillable = [
        'user_id', 'tracking_code', 'origin_city', 'origin_country',
        'destination_city', 'destination_country', 'weight_kg', 'dimensions',
        'mode', 'status', 'estimated_price', 'currency', 'picked_up_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'estimated_price' => 'decimal:2',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $shipment) {
            $shipment->tracking_code ??= 'FRT-'.strtoupper(Str::random(10));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
