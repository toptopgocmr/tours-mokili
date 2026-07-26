<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FreightOfferController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Fret/Index', [
            'offers' => $request->user()->freightOffers()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Fret/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'fret');

        $request->user()->freightOffers()->create($data);

        return redirect()->route('partner.fret.index')->with('success', 'Offre de fret publiee.');
    }

    public function edit(FreightOffer $offer): Response
    {
        $this->authorizeOwner($offer);

        return Inertia::render('Partner/Fret/Form', ['offer' => $offer]);
    }

    public function update(Request $request, FreightOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $offer->image, 'fret');

        $offer->update($data);

        return redirect()->route('partner.fret.index')->with('success', 'Offre de fret mise a jour.');
    }

    public function destroy(FreightOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);
        $offer->delete();

        return back()->with('success', 'Offre de fret supprimee.');
    }

    protected function authorizeOwner(FreightOffer $offer): void
    {
        abort_unless($offer->carrier_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'in:air,mer,route'],
            'origin_city' => ['required', 'string', 'max:255'],
            'origin_country' => ['required', 'string', 'size:2'],
            'destination_city' => ['required', 'string', 'max:255'],
            'destination_country' => ['required', 'string', 'size:2'],
            'price_per_kg' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'capacity_kg' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
