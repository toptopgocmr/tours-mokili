<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Divertissement/Index', [
            'events' => $request->user()->events()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Divertissement/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'divertissement');

        $request->user()->events()->create($data);

        return redirect()->route('partner.divertissement.index')->with('success', 'Evenement publie.');
    }

    public function edit(Event $event): Response
    {
        $this->authorizeOwner($event);

        return Inertia::render('Partner/Divertissement/Form', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($event);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $event->image, 'divertissement');

        $event->update($data);

        return redirect()->route('partner.divertissement.index')->with('success', 'Evenement mis a jour.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeOwner($event);
        $event->delete();

        return back()->with('success', 'Evenement supprime.');
    }

    protected function authorizeOwner(Event $event): void
    {
        abort_unless($event->organizer_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'capacity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
