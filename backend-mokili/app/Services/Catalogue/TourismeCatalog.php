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

        // Concerts / cinema sans evenement a venir : on montre les salles.
        if ($config['source'] === 'events' && ! empty($config['venues'])
            && ! Event::query()->active()->where('starts_at', '>=', now()->subHours(6))->whereIn('category', $config['categories'])->exists()) {
            $config = ['source' => 'businesses', 'categories' => $config['venues']];
        }

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

    /**
     * Toutes les sorties au meme format de carte (facon Boas "Sites
     * touristiques") : un onglet precis ou TOUT ($tab = null), filtre par
     * ville et par recherche texte.
     */
    public function cards(?string $tab, array $filters = []): \Illuminate\Support\Collection
    {
        $tabs = $this->tabs();
        $keys = $tab ? [$tab] : array_keys($tabs);
        $city = $filters['city'] ?? null;
        $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
        $cards = collect();

        foreach ($keys as $key) {
            $conf = $tabs[$key];
            $rows = match ($conf['source']) {
                'events' => Event::query()->active()->where('starts_at', '>=', now()->subHours(6))->whereIn('category', $conf['categories'])
                    ->when($city, fn ($w) => $w->where('city', 'like', "%{$city}%"))->orderBy('starts_at')->limit(200)->get()
                    ->map(fn (Event $e) => [
                        'key' => 'e'.$e->id, 'tab' => $key, 'title' => $e->title, 'city' => $e->city, 'address' => $e->venue,
                        'description' => $e->description, 'image' => $e->image_url, 'category' => null,
                        'price' => (float) ($e->final_price ?? $e->price) > 0 ? $money($e->final_price ?? $e->price).' '.$e->currency : 'Gratuit',
                        'date' => $e->starts_at?->toIso8601String(), 'href' => '/divertissement/'.$e->slug, 'featured' => false,
                    ]),
                'parks' => AttractionPark::query()->active()->when($city, fn ($w) => $w->where('city', 'like', "%{$city}%"))
                    ->when($filters['type'] ?? null, fn ($w, $t) => $w->where('category', $t))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(200)->get()
                    ->map(fn (AttractionPark $p) => [
                        'key' => 'p'.$p->id, 'tab' => $key, 'title' => $p->name, 'city' => $p->city, 'address' => trim(($p->district ?? '').($p->address ? ' · '.$p->address : ''), ' ·'),
                        'description' => $p->description, 'image' => $p->image_url, 'category' => $p->category_label ?? null,
                        'price' => $p->price_adult === null ? null : ((float) ($p->final_price_adult ?? $p->price_adult) > 0 ? 'Des '.$money($p->final_price_adult ?? $p->price_adult).' '.$p->currency : 'Gratuit'),
                        'date' => null, 'href' => '/divertissement/parcs/'.$p->slug, 'featured' => (bool) $p->is_featured,
                    ]),
                default => Business::query()->active()->whereIn('category', $conf['categories'])->when($city, fn ($w) => $w->where('city', 'like', "%{$city}%"))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(200)->get()
                    ->map(fn (Business $b) => $this->businessCard($b, $key)),
            };
            // Salles de concert / cinemas, apres les evenements.
            if ($conf['source'] === 'events' && ! empty($conf['venues'])) {
                $rows = $rows->concat(Business::query()->active()->whereIn('category', $conf['venues'])
                    ->when($city, fn ($w) => $w->where('city', 'like', "%{$city}%"))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(100)->get()
                    ->map(fn (Business $b) => $this->businessCard($b, $key)));
            }
            $cards = $cards->concat($rows->map(fn ($c) => $c + ['tab_label' => $conf['label']]));
        }

        if ($q !== '') {
            $cards = $cards->filter(fn ($c) => str_contains(mb_strtolower($c['title'].' '.$c['address'].' '.$c['description'].' '.$c['city']), $q));
        }

        // A la une d'abord, puis les evenements par date, puis par nom.
        return $cards->sortBy([['featured', 'desc'], fn ($a, $b) => strcmp((string) ($a['date'] ?? 'z'), (string) ($b['date'] ?? 'z')), ['title', 'asc']])->values();
    }

    protected function businessCard(Business $b, string $key): array
    {
        return [
            'key' => 'b'.$b->id, 'tab' => $key, 'title' => $b->name, 'city' => $b->city, 'address' => trim(($b->address ?? '').($b->district ? ' · '.$b->district : ''), ' ·'),
            'description' => $b->description, 'image' => $b->image_url, 'image_is_illustration' => $b->image_is_illustration,
            'category' => $b->category, 'category_label' => $b->category_label, 'business_id' => $b->id,
            'price' => $b->price_range ?: ($key === 'visite' ? 'Gratuit' : null),
            'date' => null, 'href' => '/partenaires/'.$b->slug, 'featured' => (bool) $b->is_featured,
        ];
    }

    /** Villes presentes dans la rubrique (filtre "Ville"). */
    public function cities(): array
    {
        return collect([
            ...Event::query()->active()->where('starts_at', '>=', now()->subHours(6))->pluck('city'),
            ...AttractionPark::query()->active()->pluck('city'),
            ...Business::query()->active()->whereIn('category', collect($this->tabs())->flatMap(fn ($t) => [...($t['categories'] ?? []), ...($t['venues'] ?? [])])->filter()->all())->pluck('city'),
        ])->filter()->map(fn ($c) => trim($c))->unique()->sort()->values()->all();
    }
}
