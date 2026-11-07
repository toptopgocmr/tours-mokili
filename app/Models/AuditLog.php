<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Journal des actions administratives et consultations de donnees sensibles. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'ip_address', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $subject = null, array $context = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
            'context' => $context ?: null,
        ]);
    }
}
