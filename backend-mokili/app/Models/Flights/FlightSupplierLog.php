<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightSupplierLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'flight_booking_id', 'flight_supplier_id', 'operation', 'idempotency_key', 'success',
        'outcome', 'external_ref', 'request', 'response', 'duration_ms',
    ];

    protected function casts(): array
    {
        return ['request' => 'array', 'response' => 'array', 'success' => 'boolean'];
    }
}
