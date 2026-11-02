<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Offre renvoyee par un fournisseur pour une recherche. Prix valable jusqu'a
 * expires_at ; au-dela, revalidation obligatoire avant paiement.
 */
class FlightOffer extends Model
{
    protected $fillable = [
        'uuid', 'flight_search_id', 'flight_supplier_id', 'supplier_offer_id', 'validating_airline',
        'airline_name', 'slices', 'passengers', 'fare_rules', 'supplier_amount', 'markup_amount',
        'service_fee', 'total_amount', 'currency', 'expires_at', 'price_confirmed_at', 'raw',
    ];

    protected $hidden = ['raw', 'supplier_offer_id', 'supplier_amount', 'markup_amount', 'flight_supplier_id'];

    protected $appends = ['is_expired'];

    protected function casts(): array
    {
        return [
            'slices' => 'array',
            'passengers' => 'array',
            'fare_rules' => 'array',
            'raw' => 'array',
            'supplier_amount' => 'decimal:2',
            'markup_amount' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'price_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $o) => $o->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function search()
    {
        return $this->belongsTo(FlightSearch::class, 'flight_search_id');
    }

    public function supplier()
    {
        return $this->belongsTo(FlightSupplier::class, 'flight_supplier_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }
}
