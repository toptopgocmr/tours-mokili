<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Public catalog of freight/shipping service offers published by partners
// (carriers) - distinct from ShipmentController, which tracks a customer's
// own already-created shipments.
class FreightOfferController extends Controller
{
    public function index(Request $request): Response
    {
        $offers = FreightOffer::query()->active()
            ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->string('mode')))
            ->when($request->filled('origin'), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('origin').'%'))
            ->when($request->filled('destination'), fn ($q) => $q->where('destination_city', 'like', '%'.$request->string('destination').'%'))
            ->when($request->filled('service_type'), fn ($q) => $q->where('service_type', $request->string('service_type')))
            ->when($request->input('scope') === 'national', fn ($q) => $q->whereColumn('origin_country', 'destination_country'))
            ->when($request->input('scope') === 'international', fn ($q) => $q->whereColumn('origin_country', '!=', 'destination_country'))
            ->with('partnerProfile:user_id,company_name,trade_name')
            ->latest()->paginate(9)->withQueryString();

        return Inertia::render('Fret/Index', [
            'offers' => $offers,
            'filters' => $request->only(['mode', 'scope', 'origin', 'destination', 'service_type']),
        ]);
    }

    public function show(FreightOffer $freightOffer): Response
    {
        abort_unless(FreightOffer::active()->whereKey($freightOffer->id)->exists(), 404);

        return Inertia::render('Fret/Show', ['offer' => $freightOffer->load('partnerProfile:user_id,company_name,trade_name,description')]);
    }
}
