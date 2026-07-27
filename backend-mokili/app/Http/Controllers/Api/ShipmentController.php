<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fret\Shipment;
use Illuminate\Http\JsonResponse;

/**
 * "Suivre un colis" (style DHL) - lookup by tracking code, public
 * (no login needed, matching how DHL/UPS tracking pages work).
 */
class ShipmentController extends Controller
{
    public function track(string $code): JsonResponse
    {
        $shipment = Shipment::where('tracking_code', $code)->first();

        if (! $shipment) {
            return response()->json(['message' => 'Aucun colis trouve pour ce numero de suivi.'], 404);
        }

        return response()->json($shipment);
    }
}
