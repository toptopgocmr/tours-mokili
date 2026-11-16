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

    /**
     * Voiture > Parking : parkings, gardiennage et lavage auto, plus les
     * garages / concessionnaires qui proposent ces prestations.
     */
    public static function parking(?string $city = null, ?string $term = null): Builder
    {
        $city = trim((string) $city);
        $term = trim((string) $term);
        $words = ['Parking', 'Gardiennage', 'Lavage'];

        return Business::query()->active()
            ->where(fn ($c) => $c->where('category', 'parking')->orWhere(fn ($g) => $g->whereIn('category', ['garage', 'concessionnaire'])
                ->where(fn ($x) => collect($words)->each(fn ($w) => $x->orWhere('services', 'like', '%"'.$w.'"%')))))
            ->when($city !== '', fn ($q) => $q->where('city', 'like', "%{$city}%"))
            ->when($term !== '', function ($q) use ($term) {
                $key = collect(explode('/', $term))->map(fn ($w) => trim($w))->sortByDesc(fn ($w) => strlen($w))->first() ?: $term;
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('district', 'like', "%{$term}%")
                    ->orWhere('services', 'like', '%'.$key.'%'));
            })
            ->orderByRaw("category = 'parking' desc")->orderByDesc('is_featured')->orderBy('city')->orderBy('name');
    }

    /** Prestations proposees pour la prise de rendez-vous de cette societe. */
    public static function servicesFor(Business $business): array
    {
        if ($business->category === 'parking') {
            return config('catalogue.voiture.parking_services');
        }
        if ($business->category === 'concessionnaire') {
            // Achat d'abord ; prestations atelier si la concession en a un.
            $atelier = collect($business->services ?? [])->contains(fn ($s) => (bool) preg_match('/atelier|remorquage/i', (string) $s));

            return array_values(array_merge(config('catalogue.voiture.dealer_services'), $atelier ? config('catalogue.voiture.mechanic_services') : []));
        }

        return config('catalogue.voiture.mechanic_services');
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
