<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightTicket extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(FlightOrder::class, 'flight_order_id');
    }

    public function passenger()
    {
        return $this->belongsTo(FlightPassenger::class, 'flight_passenger_id');
    }
}
