<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devis extends Model
{
    use HasFactory;

    protected $table = 'devis';

    protected $fillable = [
        'numero_devis',
        'dossier_id',
        'client_id',
        'created_by',
        'date_devis',
        'validite_jours',
        'statut',
        'sous_total',
        'taux_tva',
        'montant_tva',
        'total_ttc',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_devis' => 'date',
        ];
    }

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creePar()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lignes()
    {
        return $this->hasMany(DevisLigne::class)->orderBy('ordre');
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    public function recalculerTotaux(): void
    {
        $sousTotal = $this->lignes()->sum('montant_ht');
        $montantTva = round($sousTotal * ($this->taux_tva / 100), 2);

        $this->sous_total = $sousTotal;
        $this->montant_tva = $montantTva;
        $this->total_ttc = $sousTotal + $montantTva;
        $this->save();
    }
}
