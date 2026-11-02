<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevisLigne extends Model
{
    use HasFactory;

    protected $table = 'devis_lignes';

    protected $fillable = [
        'devis_id',
        'designation',
        'description',
        'quantite',
        'prix_unitaire',
        'montant_ht',
        'ordre',
    ];

    protected static function booted(): void
    {
        static::saving(function (DevisLigne $ligne) {
            $ligne->montant_ht = round($ligne->quantite * $ligne->prix_unitaire, 2);
        });

        static::saved(fn (DevisLigne $ligne) => $ligne->devis?->recalculerTotaux());
        static::deleted(fn (DevisLigne $ligne) => $ligne->devis?->recalculerTotaux());
    }

    public function devis()
    {
        return $this->belongsTo(Devis::class);
    }
}
