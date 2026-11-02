<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlightSearch extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'trip_type', 'origin', 'destination', 'depart_date', 'return_date',
        'adults', 'children', 'infants', 'cabin', 'results_count', 'supplier_errors',
    ];

    protected function casts(): array
    {
        return ['depart_date' => 'date', 'return_date' => 'date', 'supplier_errors' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $s) => $s->uuid ??= (string) Str::uuid());
    }

    public function offers()
    {
        return $this->hasMany(FlightOffer::class);
    }

    public function passengerCount(): int
    {
        return $this->adults + $this->children + $this->infants;
    }
}
