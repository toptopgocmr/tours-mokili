<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single SMS verification code sent for phone/OTP sign-in. See
 * PhoneOtpController and database/migrations/2026_01_04_000002_create_phone_otps_table.php.
 */
class PhoneOtp extends Model
{
    protected $fillable = [
        'phone',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
