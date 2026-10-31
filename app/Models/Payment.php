<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'booking_id', 'user_id', 'provider', 'method', 'phone',
        'country_code', 'network', 'amount', 'currency', 'status',
        'peex_track_id', 'peex_request_id', 'peex_response', 'gateway_reference',
        'gateway_response', 'failure_reason', 'last_checked_at', 'paid_at',
        'refunded_at', 'refund_reason',
    ];

    protected static function booted(): void
    {
        // UUID : exige par pawaPay (depositId) et accepte par les autres
        // passerelles comme reference marchand unique.
        static::creating(function (Payment $payment) {
            $payment->reference ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'peex_response' => 'array',
            'gateway_response' => 'array',
            'last_checked_at' => 'datetime',
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
