<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightPassenger extends Model
{
    protected $guarded = [];

    // Donnees sensibles : numero de passeport chiffre au repos et jamais serialise.
    protected $hidden = ['passport_number'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'passport_expiry' => 'date',
            'passport_number' => 'encrypted',
        ];
    }

    public function order()
    {
        return $this->belongsTo(FlightOrder::class, 'flight_order_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
