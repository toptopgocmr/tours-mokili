<?php

namespace App\Http\Controllers\Divertissement;

use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use App\Models\Loisirs\AttractionPark;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// DIVERTISSEMENT module - search UX modeled on Booking.com: city +
// category up top (see Divertissement/Index.vue), matching the same
// filters already exposed to the mobile app (Api\EventController).
// La categorie "parcs" affiche les parcs d'attractions, parcs aquatiques,
// zoos et sites naturels (ex-rubrique Loisirs).
class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['city', 'category', 'type']);
        $parksOnly = $request->string('category')->toString() === 'parcs';

        $events = $parksOnly ? null : Event::query()
            ->active()
            ->where('starts_at', '>=', now()->subHours(6))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('starts_at')
            ->paginate(9)
            ->withQueryString();

        $parks = AttractionPark::query()
            ->active()
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($parksOnly && $request->filled('type'), fn ($q) => $q->where('category', $request->string('type')))
            ->orderByDesc('is_featured')
            ->orderBy('name');

        return Inertia::render('Divertissement/Index', [
            'events' => $events,
            // "Tout" : quelques parcs a la une ; "Parcs" : catalogue complet.
            'parks' => $parksOnly
                ? $parks->paginate(12)->withQueryString()
                : ($request->filled('category') ? [] : $parks->limit(6)->get()),
            'parkCategories' => AttractionPark::CATEGORIES,
            'filters' => $filters,
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        return Inertia::render('Divertissement/Show', ['event' => $event]);
    }
}
