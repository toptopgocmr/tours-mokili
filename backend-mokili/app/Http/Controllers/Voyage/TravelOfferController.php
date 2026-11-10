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
        $isCircuit = $request->string('type')->toString() === 'circuit';

        $offers = TravelOffer::query()
            ->active()
            ->where('type', '!=', 'sejour') // rubrique Sejours retiree
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            // Trajets facon BlaBlaCar : depart, date, nombre de passagers.
            ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('from').'%'))
            ->when($request->date('date'), fn ($q) => $q->whereDate('departure_at', $request->date('date')))
            // "Partir a partir de 16h" (facon SNCF Connect).
            ->when($request->date('date') && $this->hour($request) !== null, fn ($q) => $q->where('departure_at', '>=', $request->date('date')->setTime($this->hour($request), 0)))
            // Les vols passent par la billetterie (onglet Vols), pas par les trajets.
            ->when($isCircuit, fn ($q) => $q->where(fn ($w) => $w->whereNull('transport_mode')->orWhere('transport_mode', '!=', 'avion')))
            ->when($request->integer('passengers') > 0, fn ($q) => $q->where('seats_available', '>=', $request->integer('passengers')))
            ->when($isCircuit, fn ($q) => $q->where(fn ($w) => $w->whereNull('departure_at')->orWhere('departure_at', '>=', now())))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where('transport_mode', $request->string('mode')))
            // Circuits : en ville, inter-departements, international + departement.
            ->when($request->string('scope')->isNotEmpty(), fn ($q) => $q->where('trip_scope', $request->string('scope')))
            ->when($request->string('departement')->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $w
                ->where('origin_department', $request->string('departement'))
                ->orWhere('destination_department', $request->string('departement'))))
            ->when(! $isCircuit, fn ($q) => $q->orderByDesc('is_featured'))
            ->orderByRaw('departure_at is null')
            ->orderBy('departure_at')
            ->paginate($isCircuit ? 20 : 9)
            ->withQueryString();

        // Retour (facon SNCF Connect) : memes criteres, villes inversees.
        $returnOffers = [];
        if ($isCircuit && $request->date('return_date')) {
            $returnOffers = TravelOffer::query()->active()
                ->where('type', 'circuit')
                ->where(fn ($w) => $w->whereNull('transport_mode')->orWhere('transport_mode', '!=', 'avion'))
                ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('destination').'%'))
                ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->where('destination_city', 'like', '%'.$request->string('from').'%'))
                ->whereDate('departure_at', $request->date('return_date'))
                ->where('departure_at', '>=', now())
                ->when($request->integer('passengers') > 0, fn ($q) => $q->where('seats_available', '>=', $request->integer('passengers')))
                ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where('transport_mode', $request->string('mode')))
                ->when($request->string('scope')->isNotEmpty(), fn ($q) => $q->where('trip_scope', $request->string('scope')))
                ->orderBy('departure_at')->limit(30)->get();
        }

        return Inertia::render('Voyage/Index', [
            'offers' => $offers,
            'returnOffers' => $returnOffers,
            'filters' => (object) $request->only(['destination', 'type', 'mode', 'scope', 'departement', 'from', 'date', 'time', 'return_date', 'passengers']),
            'transportModes' => TravelOffer::TRANSPORT_MODES,
            'tripScopes' => config('catalogue.voyage.trip_scopes'),
            'departments' => config('catalogue.departments'),
            'cabins' => config('flights.cabins'),
            'popularAirports' => Airport::query()->active()->orderByDesc('popularity')
                ->limit(12)->get(['iata_code', 'city', 'name', 'country_code']),
        ]);
    }

    /** Programme des departs / arrivees : trains, bus, canots rapides, taxis. */
    public function schedules(Request $request, \App\Services\Voyage\RideBoard $board): Response
    {
        return Inertia::render('Voyage/Trajets/Horaires', $board->board($request));
    }

    /** Heure de depart minimale (?time=16 ou 16:00), entre 0 et 23. */
    protected function hour(Request $request): ?int
    {
        $t = (string) $request->query('time', '');
        if ($t === '' || ! preg_match('/^(\d{1,2})/', $t, $m) || (int) $m[1] > 23) {
            return null;
        }

        return (int) $m[1];
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
