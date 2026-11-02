<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightIdempotencyKey extends Model
{
    protected $fillable = ['key', 'operation', 'flight_booking_id', 'state', 'result', 'attempts'];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
