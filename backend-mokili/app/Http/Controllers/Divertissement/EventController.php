<?php

namespace App\Http\Controllers\Divertissement;

use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// DIVERTISSEMENT module - search UX modeled on Booking.com: city +
// category up top (see Divertissement/Index.vue), matching the same
// filters already exposed to the mobile app (Api\EventController).
class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['city', 'category']);

        return Inertia::render('Divertissement/Index', [
            'events' => Event::query()
                ->active()
                ->where('starts_at', '>=', now()->subHours(6))
                ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
                ->orderBy('starts_at')
                ->paginate(9)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        return Inertia::render('Divertissement/Show', ['event' => $event]);
    }
}
