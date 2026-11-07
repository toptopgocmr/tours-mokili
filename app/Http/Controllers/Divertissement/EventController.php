<?php

namespace App\Http\Controllers\Divertissement;

use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use App\Models\Guide\Business;
use App\Models\Loisirs\AttractionPark;
use App\Services\Catalogue\TourismeCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TOURISME (ex-Divertissement). Onglets : Concerts & spectacles, Cinema,
 * Parcs, Night club, Restaurant, Bar lounge, Visite - voir
 * config/catalogue.php. "Tout" presente un apercu de chaque onglet.
 */
class EventController extends Controller
{
    public function index(Request $request, TourismeCatalog $catalog): Response
    {
        $tab = $catalog->resolveTab($request->string('tab')->toString(), $request->string('category')->toString());
        $filters = ['city' => $request->string('city')->toString() ?: null, 'type' => $request->string('type')->toString() ?: null];

        $props = [
            'tabs' => collect($catalog->tabs())->map(fn ($t) => $t['label']),
            'tab' => $tab,
            'filters' => (object) array_filter($filters + ['tab' => $tab]),
            'parkCategories' => AttractionPark::CATEGORIES,
        ];

        if ($tab) {
            $result = $catalog->items($tab, $filters);
            $props['source'] = $result['source'];
            $props['items'] = $result['items'];
        } else {
            // Apercu : prochains evenements, parcs et adresses a la une.
            $city = $filters['city'];
            $props['preview'] = [
                'events' => Event::query()->active()->where('starts_at', '>=', now()->subHours(6))
                    ->when($city, fn ($q) => $q->where('city', 'like', "%{$city}%"))
                    ->orderBy('starts_at')->limit(6)->get(),
                'parks' => AttractionPark::query()->active()
                    ->when($city, fn ($q) => $q->where('city', 'like', "%{$city}%"))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(6)->get(),
                'places' => Business::query()->active()
                    ->whereIn('category', ['night_club', 'restaurant', 'bar_lounge', 'site_touristique'])
                    ->when($city, fn ($q) => $q->where('city', 'like', "%{$city}%"))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(8)->get(),
            ];
        }

        return Inertia::render('Divertissement/Index', $props);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        return Inertia::render('Divertissement/Show', ['event' => $event]);
    }
}
