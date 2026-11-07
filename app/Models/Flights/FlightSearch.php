<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightSearch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'depart_date' => 'date',
            'return_date' => 'date',
            'supplier_errors' => 'array',
        ];
    }

    public function offers()
    {
        return $this->hasMany(FlightOffer::class);
    }

    public function passengerCount(): int
    {
        return (int) $this->adults + (int) $this->children + (int) $this->infants;
    }
}
