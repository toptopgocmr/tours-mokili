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

        return Inertia::render('Fret/Index', [
            'offers' => FreightOffer::query()->active()
                ->forScope($scope)
                ->when($mode, fn ($q) => $q->where('mode', $mode))
                ->when($package, fn ($q) => $q->where(fn ($w) => $w->whereNull('package_types')->orWhereJsonContains('package_types', $package)))
                ->when($request->filled('origine'), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('origine').'%'))
                ->when($request->filled('destination'), fn ($q) => $q->where('destination_city', 'like', '%'.$request->string('destination').'%'))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'filters' => (object) array_filter(['mode' => $mode, 'scope' => $scope, 'colis' => $package]
                + $request->only('origine', 'destination')),
        ]);
    }

    public function show(FreightOffer $freightOffer): Response
    {
        abort_unless($freightOffer->is_active && $freightOffer->status === 'published', 404);

        return Inertia::render('Fret/Show', ['offer' => $freightOffer]);
    }
}
