<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightAuditLog;
use App\Models\Flights\FlightOrder;

/**
 * Journal d'audit (ajout seul). Ne jamais y mettre de numero de passeport,
 * de carte ou de secret : seulement des identifiants et des decisions.
 */
class FlightAudit
{
    public static function log(string $action, ?FlightOrder $order = null, array $context = [], string $actor = 'system'): void
    {
        FlightAuditLog::create([
            'flight_order_id' => $order?->id,
            'action' => $action,
            'actor' => $actor,
            'ip' => request()?->ip(),
            'context' => $context ?: null,
        ]);
    }
}
