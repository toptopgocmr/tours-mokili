<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightAuditLog;
use Illuminate\Database\Eloquent\Model;

/** Journal d'audit ajout-seul (validations, tarifs, paiements, remboursements, suivi). */
class FreightAudit
{
    public static function log(string $action, ?Model $subject = null, string $actor = 'system', ?string $reason = null, array $context = []): void
    {
        FreightAuditLog::create([
            'action' => $action,
            'actor' => $actor,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason,
            'context' => $context ?: null,
        ]);
    }
}
