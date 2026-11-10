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
        $filters = [
            'city' => $request->string('city')->toString() ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'q' => $request->string('q')->toString() ?: null,
        ];

        // Toutes les cartes (TOUT = tous les onglets), puis pagination 12 par page.
        $all = $catalog->cards($tab, $filters);
        $page = max(1, $request->integer('page', 1));
        $perPage = 12;
        $counts = $tab ? null : $all->countBy('tab');

        return Inertia::render('Divertissement/Index', [
            'tabs' => collect($catalog->tabs())->map(fn ($t) => $t['label']),
            'tab' => $tab,
            'filters' => (object) array_filter($filters + ['tab' => $tab]),
            'parkCategories' => AttractionPark::CATEGORIES,
            'cities' => $catalog->cities(),
            'counts' => $counts,
            'cards' => [
                'data' => $all->forPage($page, $perPage)->values(),
                'total' => $all->count(),
                'page' => $page,
                'last_page' => max(1, (int) ceil($all->count() / $perPage)),
            ],
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        return Inertia::render('Divertissement/Show', ['event' => $event]);
    }
}
