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
        $c = config('catalogue.fret');
        $mode = isset($c['modes'][$request->string('mode')->toString()]) ? $request->string('mode')->toString() : null;
        $scope = isset($c['scopes'][$request->string('scope')->toString()]) ? $request->string('scope')->toString() : null;
        $package = isset($c['package_types'][$request->string('colis')->toString()]) ? $request->string('colis')->toString() : null;

        $offers = FreightOffer::query()->active()
            ->forScope($scope)
            ->when($mode, fn ($q) => $q->where('mode', $mode))
            ->when($package, fn ($q) => $q->where(fn ($w) => $w->whereNull('package_types')->orWhereJsonContains('package_types', $package)))
            ->when($request->filled('origin'), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('origin').'%'))
            ->when($request->filled('destination'), fn ($q) => $q->where('destination_city', 'like', '%'.$request->string('destination').'%'))
            ->when($request->filled('service_type'), fn ($q) => $q->where('service_type', $request->string('service_type')))
            ->with('partnerProfile:user_id,company_name,trade_name')
            ->latest()->paginate(9)->withQueryString();

        return Inertia::render('Fret/Index', [
            'offers' => $offers,
            'filters' => (object) array_filter(['mode' => $mode, 'scope' => $scope, 'colis' => $package] + $request->only('origin', 'destination')),
            'packageTypes' => $c['package_types'],
        ]);
    }

    public function show(FreightOffer $freightOffer): Response
    {
        abort_unless(FreightOffer::active()->whereKey($freightOffer->id)->exists(), 404);

        return Inertia::render('Fret/Show', ['offer' => $freightOffer->load('partnerProfile:user_id,company_name,trade_name,description')]);
    }
}
