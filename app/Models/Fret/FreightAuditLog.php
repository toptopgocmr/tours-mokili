<?php

namespace App\Models\Fret;

use Illuminate\Database\Eloquent\Model;

/** Journal inviolable : cree uniquement. */
class FreightAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }
}
