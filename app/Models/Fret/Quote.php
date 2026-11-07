<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quote extends Model
{
    protected $table = 'freight_quotes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'accepted_snapshot' => 'array',
            'total_amount' => 'decimal:2',
            'is_firm' => 'boolean',
            'valid_until' => 'date',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $q) => $q->reference ??= 'FRT-DEV-'.strtoupper(Str::random(6)));
    }

    public function request()
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function isExpired(): bool
    {
        return $this->valid_until->endOfDay()->isPast();
    }

    public function isOpen(): bool
    {
        return $this->status === 'sent' && ! $this->isExpired();
    }
}
