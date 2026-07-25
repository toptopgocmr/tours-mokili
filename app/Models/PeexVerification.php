<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeexVerification extends Model
{
    protected $fillable = [
        'user_id', 'country_code', 'account_number', 'is_valid',
        'account_name', 'operator', 'status', 'http_status',
        'error_message', 'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'raw_response' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
