<?php

namespace App\Models\Fret;

use Illuminate\Database\Eloquent\Model;

class FreightNotification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
