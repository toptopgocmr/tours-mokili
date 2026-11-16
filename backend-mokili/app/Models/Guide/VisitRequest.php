<?php

namespace App\Models\Guide;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande "Reserver ma visite" (site touristique), "Prendre RDV" (garage,
 * parking) ou rendez-vous vente d'un vehicule (paiement sur place), sans engagement.
 */
class VisitRequest extends Model
{
    public const STATUSES = ['nouvelle' => 'Nouvelle', 'confirmee' => 'Confirmee', 'annulee' => 'Annulee', 'terminee' => 'Terminee'];

    public const FORMULAS = ['individuel' => 'Individuel', 'groupe' => 'Groupe', 'scolaire' => 'Groupe scolaire', 'entreprise' => 'Entreprise'];

    // Vente de vehicule : objet du rendez-vous.
    public const SALE_PURPOSES = [
        'voir' => 'Voir le vehicule',
        'essai' => 'Essai routier',
        'achat' => 'Achat (paiement sur place)',
    ];

    protected $fillable = ['business_id', 'vehicle_id', 'user_id', 'name', 'phone', 'email', 'visit_date', 'people', 'formula', 'message', 'status', 'service', 'vehicle', 'visit_time'];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'people' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** Vehicule en vente (la colonne "vehicle" garde la description libre des RDV garage). */
    public function saleVehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Voiture\Vehicle::class, 'vehicle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
