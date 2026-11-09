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
        $offers = TravelOffer::query()
            ->active()
            ->where('type', '!=', 'sejour') // rubrique Sejours retiree
            ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->where('origin_city', 'like', '%'.$request->string('from').'%'))
            ->when($request->date('date'), fn ($q) => $q->whereDate('departure_at', $request->date('date')))
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
            ->orderByDesc('is_featured')
            ->paginate(10);

        return response()->json($offers);
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
