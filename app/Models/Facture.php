<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $table = 'factures';

    protected $fillable = [
        'numero_facture',
        'devis_id',
        'dossier_id',
        'client_id',
        'created_by',
        'date_facture',
        'date_echeance',
        'statut',
        'sous_total',
        'taux_tva',
        'montant_tva',
        'total_ttc',
        'montant_paye',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_facture' => 'date',
            'date_echeance' => 'date',
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

    public function devisOrigine()
    {
        return $this->belongsTo(Devis::class, 'devis_id');
    }

    public function creePar()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lignes()
    {
        return $this->hasMany(FactureLigne::class)->orderBy('ordre');
    }

    public function getSoldeAttribute(): float
    {
        return round($this->total_ttc - $this->montant_paye, 2);
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
