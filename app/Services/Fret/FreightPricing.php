<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightOffer;

/** Calcul d'un tarif instantane a partir de la grille du partenaire. Jamais presente comme definitif si la grille est une estimation. */
class FreightPricing
{
    /**
     * @param  array{weight_kg?:?float, volume_m3?:?float}  $measures
     * @return array{lines:array<int,array>, total:float, is_firm:bool, quantity:float, unit:string}
     */
    public static function instant(FreightOffer $offer, array $measures): array
    {
        $weight = (float) ($measures['weight_kg'] ?? 0);
        $volume = (float) ($measures['volume_m3'] ?? 0);

        $qty = match ($offer->unit) {
            'm3' => $volume,
            'tonne' => $weight / 1000,
            'forfait' => 1.0,
            default => $offer->mode === 'air'
                // Aerien : poids taxable = max(poids reel, poids volumetrique)
                ? max($weight, $volume * (float) config('freight.air_volumetric_kg_per_m3', 167))
                : $weight,
        };

        $qty = round($qty, 3);
        $base = round((float) $offer->price_per_kg * $qty, 2);
        $lines = [['label' => sprintf('Transport %s (%s %s x %s)', $offer->mode, $qty, $offer->unit, number_format((float) $offer->price_per_kg, 0, ',', ' ')), 'amount' => $base, 'kind' => 'included']];

        if ($offer->min_price && $base < (float) $offer->min_price) {
            $lines[] = ['label' => 'Complement pour atteindre le minimum de facturation', 'amount' => round((float) $offer->min_price - $base, 2), 'kind' => 'included'];
            $base = (float) $offer->min_price;
        }

        $total = $base;
        if ($offer->promo_percent && (! $offer->promo_until || $offer->promo_until->endOfDay()->isFuture())) {
            $discount = round($base * (float) $offer->promo_percent / 100, 2);
            $lines[] = ['label' => 'Promotion '.$offer->promo_percent.' %', 'amount' => -$discount, 'kind' => 'included'];
            $total = round($base - $discount, 2);
        }

        if ($offer->exclusions) {
            $lines[] = ['label' => 'Non inclus : '.$offer->exclusions, 'amount' => 0, 'kind' => 'excluded'];
        }

        return ['lines' => $lines, 'total' => $total, 'is_firm' => (bool) $offer->price_is_firm, 'quantity' => $qty, 'unit' => $offer->unit];
    }
}
