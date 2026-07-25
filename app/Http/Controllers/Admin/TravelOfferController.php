<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Full CRUD on the VOYAGE catalogue, reserved to admin/agent staff
 * (see routes/web.php "admin." group). This is the reference
 * implementation to copy for the other 5 modules' back-office CRUD.
 */
class TravelOfferController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Voyage/Index', [
            'offers' => TravelOffer::query()
                ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(
                    'title', 'like', '%'.$request->string('search').'%'
                ))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Voyage/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        TravelOffer::create($this->validated($request));

        return redirect()->route('admin.voyage.index')->with('success', 'Offre creee avec succes.');
    }

    public function edit(TravelOffer $travelOffer): Response
    {
        return Inertia::render('Admin/Voyage/Form', ['offer' => $travelOffer]);
    }

    public function update(Request $request, TravelOffer $travelOffer): RedirectResponse
    {
        $travelOffer->update($this->validated($request));

        return redirect()->route('admin.voyage.index')->with('success', 'Offre mise a jour.');
    }

    public function destroy(TravelOffer $travelOffer): RedirectResponse
    {
        $travelOffer->delete();

        return back()->with('success', 'Offre supprimee.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:vol,sejour,circuit'],
            'description' => ['nullable', 'string'],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_country' => ['nullable', 'string', 'size:2'],
            'destination_city' => ['required', 'string', 'max:255'],
            'destination_country' => ['required', 'string', 'size:2'],
            'airline' => ['nullable', 'string', 'max:255'],
            'departure_at' => ['nullable', 'date'],
            'return_at' => ['nullable', 'date', 'after_or_equal:departure_at'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'currency' => ['required', 'string', 'size:3'],
            'seats_available' => ['required', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
