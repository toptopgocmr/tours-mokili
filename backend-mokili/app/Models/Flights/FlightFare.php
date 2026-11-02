<?php

namespace App\Models\Flights;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/** Tarif negocie saisi par l'equipe (fournisseur "manuel"). */
class FlightFare extends Model
{
    protected $fillable = [
        'flight_supplier_id', 'airline_code', 'airline_name', 'flight_number', 'origin', 'destination',
        'departure_time', 'arrival_time', 'arrival_day_offset', 'duration_minutes', 'stops', 'via',
        'days_of_week', 'valid_from', 'valid_to', 'cabin', 'fare_family', 'base_fare', 'taxes',
        'child_percent', 'infant_percent', 'currency', 'baggage', 'cabin_baggage', 'refundable',
        'changeable', 'change_fee', 'cancel_fee', 'conditions', 'seats_available',
        'ticketing_delay_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'base_fare' => 'decimal:2',
            'taxes' => 'decimal:2',
            'change_fee' => 'decimal:2',
            'cancel_fee' => 'decimal:2',
            'refundable' => 'boolean',
            'changeable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(FlightSupplier::class, 'flight_supplier_id');
    }

    /** Le tarif est-il vendable pour un depart a cette date ? */
    public function operatesOn(CarbonInterface $date): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->valid_from && $date->lt($this->valid_from->startOfDay())) {
            return false;
        }
        if ($this->valid_to && $date->gt($this->valid_to->endOfDay())) {
            return false;
        }
        $days = array_map('intval', $this->days_of_week ?? []);

        return $days === [] || in_array($date->dayOfWeekIso, $days, true);
    }
}
