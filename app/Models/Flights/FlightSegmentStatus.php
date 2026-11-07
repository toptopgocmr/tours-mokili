<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightSegmentStatus extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['flight_date' => 'date', 'checked_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(FlightOrder::class, 'flight_order_id');
    }
}
