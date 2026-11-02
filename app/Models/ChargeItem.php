<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id',
        'equipement',
        'equipement_autre',
        'quantite',
        'puissance_w',
        'priorite',
    ];

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }
}
