<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightSupplier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }
}
