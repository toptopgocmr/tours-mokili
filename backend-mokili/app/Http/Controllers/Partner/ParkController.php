<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Admin\AttractionParkController as AdminParkController;
use App\Models\Booking;
use App\Models\Loisirs\AttractionPark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace partenaire - PARCS D'ATTRACTIONS / LOISIRS (parcs aquatiques, zoos,
 * aires de jeux, iles de loisirs...) : le partenaire gere ses propres parcs
 * (tarifs adulte / enfant, horaires, activites), vendus en billets dans
 * Tourisme > Parcs. Memes regles de saisie que l'admin (validated()).
 * Visible apres validation du partenaire par MOKILI TOUR.
 */
class ParkController extends AdminParkController
{
    public function index(Request $request): Response
    {
        $parks = AttractionPark::query()->where('owner_id', $request->user()->id)->orderBy('name')->get()
            ->map(fn (AttractionPark $p) => $p->toArray() + [
                'tickets_sold' => (int) Booking::where('bookable_type', AttractionPark::class)->where('bookable_id', $p->id)
                    ->whereIn('status', BookingController::PAID)->sum('quantity'),
            ]);

        return Inertia::render('Partner/Parcs/Index', ['parks' => $parks, 'verified' => $request->user()->isVerifiedPartner()]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Parcs/Form', ['categories' => AttractionPark::CATEGORIES, 'cities' => AttractionPark::CITIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->partnerData($request);
        $data['image'] = $this->resolveImagePath($request, null, 'loisirs');
        $data['owner_id'] = $request->user()->id;
        AttractionPark::create($data);

        return redirect()->route('partner.parcs.index')->with('success', $request->user()->isVerifiedPartner()
            ? 'Parc publie dans Tourisme > Parcs.' : 'Parc enregistre : il sera visible apres validation de votre compte.');
    }

    public function edit(AttractionPark $park): Response
    {
        $this->authorizeOwner($park);

        return Inertia::render('Partner/Parcs/Form', ['park' => $park, 'categories' => AttractionPark::CATEGORIES, 'cities' => AttractionPark::CITIES]);
    }

    public function update(Request $request, AttractionPark $park): RedirectResponse
    {
        $this->authorizeOwner($park);
        $data = $this->partnerData($request);
        $data['image'] = $this->resolveImagePath($request, $park->image, 'loisirs');
        $park->update($data);

        return redirect()->route('partner.parcs.index')->with('success', 'Parc mis a jour.');
    }

    public function destroy(AttractionPark $park): RedirectResponse
    {
        $this->authorizeOwner($park);
        if (Booking::where('bookable_type', AttractionPark::class)->where('bookable_id', $park->id)->exists()) {
            $park->update(['is_active' => false]);

            return back()->with('success', 'Ce parc a des billets vendus : il a ete masque au lieu d\'etre supprime.');
        }
        $park->delete();

        return back()->with('success', 'Parc supprime.');
    }

    protected function partnerData(Request $request): array
    {
        $data = $this->validated($request);
        unset($data['is_featured']); // mise en avant reservee a MOKILI TOUR
        $data['is_active'] = $request->user()->isVerifiedPartner() ? (bool) ($data['is_active'] ?? true) : false;

        return $data;
    }

    protected function authorizeOwner(AttractionPark $park): void
    {
        abort_unless($park->owner_id === request()->user()->id, 403);
    }
}
