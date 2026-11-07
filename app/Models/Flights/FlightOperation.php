<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightOperation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['request' => 'array', 'response' => 'array'];
    }
}
