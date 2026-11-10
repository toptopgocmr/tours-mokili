<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

/**
 * Promotion d'une offre partenaire : remise en % (discount_percent), libelle
 * (promo_label) et date de fin (promo_ends_at). Une fois la date passee, la
 * remise vaut 0 automatiquement (pas de tache planifiee necessaire).
 */
trait HasPromotion
{
    public function getDiscountPercentAttribute($value): int
    {
        $end = $this->attributes['promo_ends_at'] ?? null;

        return $end && Carbon::parse($end)->isPast() ? 0 : (int) $value;
    }

    /** Prix apres remise (null si pas de prix). */
    public function promoPrice($amount): ?float
    {
        return $amount === null ? null : round((float) $amount * (1 - $this->discount_percent / 100), 2);
    }

    /** Resume de la promotion en cours pour l'affichage (null si aucune). */
    public function getPromoAttribute(): ?array
    {
        if ($this->discount_percent <= 0) {
            return null;
        }
        $end = $this->attributes['promo_ends_at'] ?? null;

        return [
            'percent' => $this->discount_percent,
            'label' => $this->attributes['promo_label'] ?? null,
            'ends_at' => $end ? Carbon::parse($end)->toIso8601String() : null,
        ];
    }
}
