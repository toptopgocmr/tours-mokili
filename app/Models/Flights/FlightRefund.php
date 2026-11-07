<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class FlightRefund extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'supplier_fee' => 'decimal:2',
            'psp_fee' => 'decimal:2',
            'penalty' => 'decimal:2',
            'refundable_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(FlightOrder::class, 'flight_order_id');
    }
}
