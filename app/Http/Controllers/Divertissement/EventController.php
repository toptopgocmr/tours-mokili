<?php

namespace App\Http\Controllers\Divertissement;

use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use Inertia\Inertia;
use Inertia\Response;

// DIVERTISSEMENT module (skeleton).
class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Divertissement/Index', [
            'events' => Event::query()->active()->orderBy('starts_at')->paginate(9),
        ]);
    }

    public function show(Event $event): Response
    {
        return Inertia::render('Divertissement/Show', ['event' => $event]);
    }
}
