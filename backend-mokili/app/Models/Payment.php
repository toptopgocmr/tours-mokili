<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id', 'user_id', 'provider', 'method', 'amount', 'currency',
        'status', 'peex_track_id', 'peex_request_id', 'peex_response', 'paid_at',
        'refunded_at', 'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'peex_response' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
