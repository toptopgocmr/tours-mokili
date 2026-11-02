<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

/** Fournisseur aerien / consolidateur (les secrets restent dans l'environnement). */
class FlightSupplier extends Model
{
    protected $fillable = ['code', 'name', 'driver', 'is_active', 'priority', 'settings', 'connection_status', 'last_checked_at'];

    protected $hidden = ['settings'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array', 'last_checked_at' => 'datetime'];
    }

    public function fares()
    {
        return $this->hasMany(FlightFare::class);
    }
}
