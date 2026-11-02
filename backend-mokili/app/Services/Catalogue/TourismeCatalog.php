<?php

namespace App\Services\Catalogue;

use App\Models\Divertissement\Event;
use App\Models\Guide\Business;
use App\Models\Loisirs\AttractionPark;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Rubrique TOURISME (ex-Divertissement) : chaque onglet lit sa source
 * (evenements, parcs ou etablissements partenaires), voir
 * config/catalogue.php -> tourisme.tabs. Partage par le site et l'API.
 */
class TourismeCatalog
{
    public function tabs(): array
    {
        return config('catalogue.tourisme.tabs');
    }

    /** Normalise les anciennes adresses (?category=parcs, ?category=concert...). */
    public function resolveTab(?string $tab, ?string $legacyCategory = null): ?string
    {
        $tabs = $this->tabs();
        if ($tab && isset($tabs[$tab])) {
            return $tab;
        }

        return match ($legacyCategory) {
            'parcs' => 'parcs',
            'concert', 'spectacle' => 'concerts',
            'cinema' => 'cinema',
            default => null,
        };
    }

    /** @return array{source: string, items: LengthAwarePaginator} */
    public function items(string $tab, array $filters = [], int $perPage = 12): array
    {
        $config = $this->tabs()[$tab];
        $city = $filters['city'] ?? null;

        $items = match ($config['source']) {
            'events' => Event::query()->active()
                ->where('starts_at', '>=', now()->subHours(6))
                ->whereIn('category', $config['categories'])
                ->when($city, fn ($q) => $q->where(fn ($w) => $w->where('city', 'like', "%{$city}%")->orWhere('venue', 'like', "%{$city}%")))
                ->orderBy('starts_at')
                ->paginate($perPage),
            'parks' => AttractionPark::query()->active()
                ->when($city, fn ($q) => $q->where('city', 'like', "%{$city}%"))
                ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('category', $type))
                ->orderByDesc('is_featured')->orderBy('name')
                ->paginate($perPage),
            default => Business::query()->active()
                ->whereIn('category', $config['categories'])
                ->when($city, fn ($q) => $q->where('city', 'like', "%{$city}%"))
                ->orderByDesc('is_featured')->orderBy('name')
                ->paginate($perPage),
        };

        return ['source' => $config['source'], 'items' => $items->withQueryString()];
    }
}
