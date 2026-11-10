<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - VOYAGE module (pilot module).
 */
class TravelOfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $isCircuit = $request->string('type')->toString() === 'circuit';
        $hour = preg_match('/^(\d{1,2})/', (string) $request->query('time', ''), $m) && (int) $m[1] <= 23 ? (int) $m[1] : null;

        $offers = TravelOffer::query()
            ->active()
            ->where('type', '!=', 'sejour') // rubrique Sejours retiree
            ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('from').'%'))
            ->when($request->date('date'), fn ($q) => $q->whereDate('departure_at', $request->date('date')))
            // "Partir a partir de 16h" (comme le site, facon SNCF Connect).
            ->when($request->date('date') && $hour !== null, fn ($q) => $q->where('departure_at', '>=', $request->date('date')->setTime($hour, 0)))
            // Les vols passent par la billetterie, pas par les trajets.
            ->when($isCircuit, fn ($q) => $q->where(fn ($w) => $w->whereNull('transport_mode')->orWhere('transport_mode', '!=', 'avion')))
            ->when($request->integer('passengers') > 0, fn ($q) => $q->where('seats_available', '>=', $request->integer('passengers')))
            ->when($request->string('type')->toString() === 'circuit', fn ($q) => $q->where(fn ($w) => $w->whereNull('departure_at')->orWhere('departure_at', '>=', now())))
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            // Circuits par moyen de transport : avion, bus, bateau, train, voiture, combine.
            ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where('transport_mode', $request->string('mode')))
            ->when($request->string('scope')->isNotEmpty(), fn ($q) => $q->where('trip_scope', $request->string('scope')))
            ->when($request->string('departement')->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $w
                ->where('origin_department', $request->string('departement'))
                ->orWhere('destination_department', $request->string('departement'))))
            ->when(! $isCircuit, fn ($q) => $q->orderByDesc('is_featured'))
            ->orderByRaw('departure_at is null')
            ->orderBy('departure_at')
            ->paginate($isCircuit ? 30 : 10)
            ->withQueryString();

        // Retour : memes criteres, villes inversees (?return_date=).
        $returns = [];
        if ($isCircuit && $request->date('return_date')) {
            $returns = TravelOffer::query()->active()->where('type', 'circuit')
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

        return response()->json($offers->toArray() + ['return_offers' => $returns]);
    }

    /** Programme des departs / arrivees (?city=Brazzaville&direction=departures|arrivals&mode=bus&date=2026-11-10). */
    public function schedules(Request $request, \App\Services\Voyage\RideBoard $board): JsonResponse
    {
        return response()->json($board->board($request));
    }

    /** Gares du monde (Transitland) : ?q=Paris Gare de Lyon */
    public function worldStations(Request $request, \App\Services\Voyage\WorldTransitBoard $world): JsonResponse
    {
        return response()->json($world->stations((string) $request->query('q', '')) + ['configured' => \App\Services\Voyage\WorldTransitBoard::configured()]);
    }

    /** Departs d'une gare du monde : ?gare=<onestop_id>&mode=train|bus|metro|ferry&date=YYYY-MM-DD */
    public function worldDepartures(Request $request, \App\Services\Voyage\WorldTransitBoard $world): JsonResponse
    {
        $family = array_key_exists((string) $request->query('mode'), \App\Services\Voyage\WorldTransitBoard::FAMILIES) ? (string) $request->query('mode') : '';
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? (string) $request->query('date') : null;

        return response()->json($world->departures((string) $request->query('gare', ''), $family, $date) + ['configured' => \App\Services\Voyage\WorldTransitBoard::configured()]);
    }

    public function modes(): JsonResponse
    {
        return response()->json(collect(TravelOffer::TRANSPORT_MODES)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values());
    }

    public function show(TravelOffer $travelOffer): JsonResponse
    {
        return response()->json($travelOffer);
    }
}
