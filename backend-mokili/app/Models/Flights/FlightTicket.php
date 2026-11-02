<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightTicket extends Model
{
    protected $fillable = ['flight_booking_id', 'flight_passenger_id', 'ticket_number', 'status', 'issued_at', 'raw'];

    protected $hidden = ['raw'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'raw' => 'array'];
    }

    public function passenger()
    {
        return $this->belongsTo(FlightPassenger::class, 'flight_passenger_id');
    }
}
