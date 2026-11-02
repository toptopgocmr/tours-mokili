<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dossier extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_dossier',
        'client_id',
        'agent_id',
        'date_intervention',
        'heure_intervention',
        'responsable_technique',
        'type_site',
        'niveaux',
        'objectifs',
        'type_reseau',
        'tension_v',
        'frequence_hz',
        'puissance_souscrite_kva',
        'facture_e2c',
        'groupe_marque',
        'groupe_puissance_kva',
        'groupe_annee',
        'groupe_problemes',
        'tension_p1',
        'tension_p2',
        'tension_p3',
        'courant_a',
        'frequence_mesuree_hz',
        'facteur_puissance',
        'puissance_active_kw',
        'puissance_apparente_kva',
        'puissance_reactive_kvar',
        'type_toiture',
        'orientation',
        'inclinaison_deg',
        'surface_m2',
        'ombrage',
        'recommandation_type',
        'puissance_pv_kwc',
        'onduleur',
        'batteries',
        'observations',
        'signature_client_path',
        'signature_ssea_path',
        'latitude',
        'longitude',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_intervention' => 'date',
            'objectifs' => 'array',
            'groupe_problemes' => 'array',
            'facture_e2c' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function chargeItems()
    {
        return $this->hasMany(ChargeItem::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function devis()
    {
        return $this->hasMany(Devis::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
