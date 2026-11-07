<?php

namespace App\Models\Fret;

use Illuminate\Database\Eloquent\Model;

/** Evenement de suivi : ajout seul ; une erreur se corrige par un nouvel evenement (corrects_event_id). */
class TrackingEvent extends Model
{
    protected $table = 'freight_tracking_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
