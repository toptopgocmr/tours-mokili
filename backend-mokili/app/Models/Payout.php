<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Decaissement Mobile Money (PAY-OUT) - voir App\Services\Payments\PaymentService. */
class Payout extends Model
{
    protected $fillable = [
        'reference', 'gateway', 'user_id', 'booking_id', 'payment_id', 'created_by',
        'beneficiary_name', 'phone', 'country_code', 'network', 'amount', 'currency',
        'reason', 'status', 'gateway_reference', 'gateway_response', 'failure_reason',
        'paid_at', 'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payout) {
            $payout->reference ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
