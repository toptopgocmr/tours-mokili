<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'country_code', 'account_number', 'operator',
        'account_name', 'peex_status', 'peex_verified_at', 'balance',
    ];

    protected function casts(): array
    {
        return [
            'peex_verified_at' => 'datetime',
            'balance' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return ! is_null($this->peex_verified_at) && $this->peex_status === 'ACTIVE';
    }
}
