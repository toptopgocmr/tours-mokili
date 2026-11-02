<?php

namespace App\Http\Controllers\Voyage;

use App\Http\Controllers\Controller;
use App\Models\Flights\Airport;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOYAGE - pilot module. Public catalogue: list + detail pages.
 */
class TravelOfferController extends Controller
{
    public function index(Request $request): Response
    {
        $offers = TravelOffer::query()
            ->active()
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where('transport_mode', $request->string('mode')))
            ->orderByDesc('is_featured')
            ->orderBy('departure_at')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Voyage/Index', [
            'offers' => $offers,
            'filters' => $request->only(['destination', 'type', 'mode']),
            'transportModes' => TravelOffer::TRANSPORT_MODES,
            'cabins' => config('flights.cabins'),
            'popularAirports' => Airport::query()->active()->orderByDesc('popularity')
                ->limit(12)->get(['iata_code', 'city', 'name', 'country_code']),
        ]);
    }

    public function show(TravelOffer $travelOffer): Response
    {
        // Offre "vol" : la reservation passe par la billetterie (passagers,
        // prix revalide, emission) - on prepare la recherche correspondante.
        $flightSearch = null;
        if ($travelOffer->type === 'vol') {
            $from = Airport::query()->active()->where('city', $travelOffer->origin_city ?: 'Brazzaville')->orderByDesc('popularity')->value('iata_code');
            $to = Airport::query()->active()->where('city', $travelOffer->destination_city)->orderByDesc('popularity')->value('iata_code');
            $depart = $travelOffer->departure_at && $travelOffer->departure_at->isFuture() ? $travelOffer->departure_at->toDateString() : now()->addDays(14)->toDateString();
            if ($from && $to && $from !== $to) {
                $flightSearch = array_filter([
                    'origin' => $from,
                    'destination' => $to,
                    'depart_date' => $depart,
                    'return_date' => $travelOffer->return_at?->isFuture() ? $travelOffer->return_at->toDateString() : null,
                    'trip_type' => $travelOffer->return_at?->isFuture() ? 'roundtrip' : 'oneway',
                    'adults' => 1,
                    'cabin' => 'economy',
                ]);
            }
        }

        return Inertia::render('Voyage/Show', [
            'offer' => $travelOffer,
            'flightSearch' => $flightSearch,
        ]);
    }
}
