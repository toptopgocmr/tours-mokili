<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id',
        'type',
        'fichier_path',
        'latitude',
        'longitude',
    ];

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->fichier_path);
    }
}
