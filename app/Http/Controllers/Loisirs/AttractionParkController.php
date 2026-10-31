<?php

namespace App\Http\Controllers\Loisirs;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParkBookingRequest;
use App\Models\Loisirs\AttractionPark;
use App\Services\Loisirs\ParkBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

// LOISIRS module - site web public (Inertia).
class AttractionParkController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Loisirs/Index', [
            'parks' => AttractionPark::query()
                ->active()
                ->when($request->filled('ville'), fn ($q) => $q->where('city', $request->string('ville')))
                ->when($request->filled('type'), fn ($q) => $q->where('category', $request->string('type')))
                ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'filters' => $request->only('ville', 'type', 'q'),
            'categories' => AttractionPark::CATEGORIES,
            'cities' => AttractionPark::query()->active()->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function show(AttractionPark $attractionPark): Response
    {
        abort_unless($attractionPark->is_active, 404);

        return Inertia::render('Loisirs/Show', [
            'park' => $attractionPark,
            'similar' => AttractionPark::query()
                ->active()
                ->where('id', '!=', $attractionPark->id)
                ->where('city', $attractionPark->city)
                ->limit(3)
                ->get(),
        ]);
    }

    public function book(StoreParkBookingRequest $request, AttractionPark $attractionPark, ParkBookingService $service): RedirectResponse
    {
        $booking = $service->create($attractionPark, Auth::id(), $request->validated());

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Billets reserves. Choisissez votre moyen de paiement pour confirmer.');
    }
}
