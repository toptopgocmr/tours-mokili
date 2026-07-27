<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The 6 pillars of MOKILI TOUR: Voyage, Logement, Voiture,
 * Divertissement, Marketplace, Fret.
 */
class ServiceCategory extends Model
{
    protected $fillable = [
        'slug', 'name', 'icon', 'color', 'description', 'is_active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
