<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Reglage back-office d'une passerelle de paiement (activation + pays preferes). */
class GatewaySetting extends Model
{
    protected $fillable = ['gateway', 'collect_enabled', 'payout_enabled', 'preferred_countries', 'position'];

    protected function casts(): array
    {
        return [
            'collect_enabled' => 'boolean',
            'payout_enabled' => 'boolean',
            'preferred_countries' => 'array',
        ];
    }
}
