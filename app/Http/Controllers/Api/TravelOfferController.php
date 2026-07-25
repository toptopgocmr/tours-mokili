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
            ->orderByDesc('is_featured')
            ->paginate(10);

        return response()->json($offers);
    }

    public function show(TravelOffer $travelOffer): JsonResponse
    {
        return response()->json($travelOffer);
    }
}
