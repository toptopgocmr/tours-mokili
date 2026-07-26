<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Logement\LodgingListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LodgingListingController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Logement/Index', [
            'listings' => $request->user()->lodgingListings()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Logement/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'logement');

        $request->user()->lodgingListings()->create($data);

        return redirect()->route('partner.logement.index')->with('success', 'Logement publie.');
    }

    public function edit(LodgingListing $listing): Response
    {
        $this->authorizeOwner($listing);

        return Inertia::render('Partner/Logement/Form', ['listing' => $listing]);
    }

    public function update(Request $request, LodgingListing $listing): RedirectResponse
    {
        $this->authorizeOwner($listing);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $listing->image, 'logement');

        $listing->update($data);

        return redirect()->route('partner.logement.index')->with('success', 'Logement mis a jour.');
    }

    public function destroy(LodgingListing $listing): RedirectResponse
    {
        $this->authorizeOwner($listing);
        $listing->delete();

        return back()->with('success', 'Logement supprime.');
    }

    protected function authorizeOwner(LodgingListing $listing): void
    {
        abort_unless($listing->owner_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'address' => ['nullable', 'string', 'max:255'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'max_guests' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
