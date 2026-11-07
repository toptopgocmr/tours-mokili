<?php

namespace App\Http\Controllers\Voyage;

use App\Http\Controllers\Controller;
use App\Models\Flights\Airport;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOYAGE : Vols (billetterie, moteur /vols) et Circuits (trajets en bus,
 * bateau, canot, train, voiture - recherche facon covoiturage : depart,
 * arrivee, date, passagers). Les anciens "sejours" ne sont plus proposes.
 */
class TravelOfferController extends Controller
{
    public function index(Request $request): Response
    {
        $type = in_array($request->string('type')->toString(), ['vol', 'circuit'], true) ? $request->string('type')->toString() : '';
        $isCircuit = $type === 'circuit';
        $sort = $request->string('tri')->toString() === 'prix' ? 'prix' : 'heure';

        $offers = TravelOffer::query()
            ->active()
            ->whereIn('type', ['vol', 'circuit'])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($request->filled('origin'), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('origin').'%'))
            ->when($request->filled('destination'), fn ($q) => $q->where('destination_city', 'like', '%'.$request->string('destination').'%'))
            ->when($isCircuit && $request->filled('date'), fn ($q) => $q->whereDate('departure_at', '>=', $request->string('date')->toString()))
            ->when($isCircuit && $request->integer('passagers') > 1, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('seats_available')->orWhere('seats_available', '>=', $request->integer('passagers'))))
            ->when($isCircuit && $request->filled('mode'), fn ($q) => $q->where('transport_mode', $request->string('mode')))
            ->when($isCircuit && $request->filled('scope'), fn ($q) => $q->where('trip_scope', $request->string('scope')))
            ->when($isCircuit && $request->filled('departement'), fn ($q) => $q->where(fn ($w) => $w
                ->where('origin_department', $request->string('departement'))
                ->orWhere('destination_department', $request->string('departement'))))
            ->when($sort === 'prix', fn ($q) => $q->orderBy('price'), fn ($q) => $q->orderByDesc('is_featured')->orderBy('departure_at'))
            ->paginate($isCircuit ? 12 : 9)
            ->withQueryString();

        return Inertia::render('Voyage/Index', [
            'offers' => $offers,
            'filters' => (object) $request->only(['origin', 'destination', 'type', 'mode', 'scope', 'departement', 'date', 'passagers', 'tri']),
            'transportModes' => TravelOffer::TRANSPORT_MODES,
            'tripScopes' => config('catalogue.voyage.trip_scopes'),
            'departments' => config('catalogue.departments'),
            'airports' => Airport::query()->orderBy('city')->get(['iata', 'city', 'name', 'country']),
        ]);
    }

    public function show(TravelOffer $travelOffer): Response
    {
        return Inertia::render('Voyage/Show', ['offer' => $travelOffer]);
    }
}
