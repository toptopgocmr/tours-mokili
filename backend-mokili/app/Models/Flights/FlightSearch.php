<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlightSearch extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'trip_type', 'origin', 'destination', 'depart_date', 'return_date', 'legs',
        'adults', 'children', 'infants', 'cabin', 'results_count', 'supplier_errors',
    ];

    protected function casts(): array
    {
        return ['depart_date' => 'date', 'return_date' => 'date', 'supplier_errors' => 'array', 'legs' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $s) => $s->uuid ??= (string) Str::uuid());
    }

    public function offers()
    {
        return $this->hasMany(FlightOffer::class);
    }

    public function isMultiCity(): bool
    {
        return $this->trip_type === 'multicity' && is_array($this->legs) && count($this->legs) >= 2;
    }

    /** Criteres de recherche (pour relancer ou revalider une offre). */
    public function criteriaArray(): array
    {
        return $this->only(['origin', 'destination', 'trip_type', 'legs', 'adults', 'children', 'infants', 'cabin']) + [
            'depart_date' => $this->depart_date->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
        ];
    }

    /** Tous les aeroports du voyage (departs, escales choisies, arrivees). */
    public function airportCodes(): array
    {
        $codes = [$this->origin, $this->destination];
        foreach ($this->isMultiCity() ? $this->legs : [] as $leg) {
            $codes[] = $leg['origin'];
            $codes[] = $leg['destination'];
        }

        return array_values(array_unique(array_filter($codes)));
    }

    /** Date du dernier vol du voyage. */
    public function lastTravelDate(): \Illuminate\Support\Carbon
    {
        if ($this->isMultiCity()) {
            $legs = $this->legs;

            return \Illuminate\Support\Carbon::parse($legs[count($legs) - 1]['date'])->startOfDay();
        }

        return ($this->return_date ?? $this->depart_date)->copy();
    }

    public function passengerCount(): int
    {
        return $this->adults + $this->children + $this->infants;
    }
}
