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
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            // Circuits par moyen de transport : avion, bus, bateau, train, voiture, combine.
            ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where('transport_mode', $request->string('mode')))
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
