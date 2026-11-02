<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureLigne extends Model
{
    use HasFactory;

    protected $table = 'facture_lignes';

    protected $fillable = [
        'facture_id',
        'designation',
        'description',
        'quantite',
        'prix_unitaire',
        'montant_ht',
        'ordre',
    ];

    protected static function booted(): void
    {
        static::saving(function (FactureLigne $ligne) {
            $ligne->montant_ht = round($ligne->quantite * $ligne->prix_unitaire, 2);
        });

        static::saved(fn (FactureLigne $ligne) => $ligne->facture?->recalculerTotaux());
        static::deleted(fn (FactureLigne $ligne) => $ligne->facture?->recalculerTotaux());
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
