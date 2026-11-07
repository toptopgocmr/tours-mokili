<?php

namespace App\Http\Controllers\Loisirs;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParkBookingRequest;
use App\Models\Loisirs\AttractionPark;
use App\Services\Loisirs\ParkBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

// Parcs d'attractions - site web public (Inertia), affiches dans la
// rubrique Divertissement (liste : Divertissement\EventController).
class AttractionParkController extends Controller
{
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
