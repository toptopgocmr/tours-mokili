<?php

namespace App\Models\Guide;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Demande "Reserver ma visite" sur un site touristique (sans engagement). */
class VisitRequest extends Model
{
    public const STATUSES = ['nouvelle' => 'Nouvelle', 'confirmee' => 'Confirmee', 'annulee' => 'Annulee', 'terminee' => 'Terminee'];

    public const FORMULAS = ['individuel' => 'Individuel', 'groupe' => 'Groupe', 'scolaire' => 'Groupe scolaire', 'entreprise' => 'Entreprise'];

    protected $fillable = ['business_id', 'user_id', 'name', 'phone', 'email', 'visit_date', 'people', 'formula', 'message', 'status'];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'people' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
