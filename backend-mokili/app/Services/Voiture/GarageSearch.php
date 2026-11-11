<?php

namespace App\Services\Voiture;

use App\Models\Guide\Business;
use Illuminate\Database\Eloquent\Builder;

/**
 * Voiture > Mecanique : garages, depanneurs et ateliers des concessionnaires,
 * filtres par ville et par prestation (site web + app mobile).
 */
class GarageSearch
{
    public static function query(?string $city = null, ?string $term = null): Builder
    {
        $city = trim((string) $city);
        $term = trim((string) $term);

        return Business::query()->active()
            // Garages + concessionnaires qui ont un atelier ou un service de depannage.
            ->where(fn ($c) => $c->where('category', 'garage')->orWhere(fn ($d) => $d->where('category', 'concessionnaire')
                ->where(fn ($x) => $x->where('services', 'like', '%Atelier%')->orWhere('services', 'like', '%remorquage%'))))
            ->when($city !== '', fn ($q) => $q->where('city', 'like', "%{$city}%"))
            ->when($term !== '', function ($q) use ($term) {
                // "Depannage / remorquage" : le "/" peut etre echappe dans le JSON, on cherche aussi le mot principal.
                $key = collect(explode('/', $term))->map(fn ($w) => trim($w))->sortByDesc(fn ($w) => strlen($w))->first() ?: $term;
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('district', 'like', "%{$term}%")
                    ->orWhere('services', 'like', '%'.$key.'%')
                    ->orWhere('services', 'like', "%{$term}%"));
            })
            ->orderByDesc('is_featured')->orderBy('city')->orderBy('name');
    }

    /** Vendeurs / concessionnaires partenaires qui proposent cette marque. */
    public static function dealersFor(?string $brand, ?string $city = null)
    {
        if (! $brand) {
            return collect();
        }

        return Business::query()->active()->where('category', 'concessionnaire')
            ->where(fn ($q) => $q->where('services', 'like', '%"'.$brand.'"%')->orWhere('services', 'like', '%Multimarque%'))
            ->orderByRaw('city = ? desc', [(string) $city])->orderByDesc('is_featured')->limit(4)
            ->get(['id', 'name', 'slug', 'city', 'phone', 'category', 'services', 'image']);
    }
}
