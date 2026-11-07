<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightOffer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'itinerary' => 'array',
            'fare_rules' => 'array',
            'raw' => 'array',
            'price_total' => 'decimal:2',
            'supplier_cost' => 'decimal:2',
            'expires_at' => 'datetime',
            'price_confirmed_at' => 'datetime',
        ];
    }

    // Le cout fournisseur n'est jamais expose au client.
    protected $hidden = ['supplier_cost', 'external_id', 'raw'];

    public function search()
    {
        return $this->belongsTo(FlightSearch::class, 'flight_search_id');
    }

    public function supplier()
    {
        return $this->belongsTo(FlightSupplier::class, 'flight_supplier_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
