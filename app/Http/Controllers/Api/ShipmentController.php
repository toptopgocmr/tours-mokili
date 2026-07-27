<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fret\Shipment;
use Illuminate\Http\JsonResponse;

/**
 * JSON API for the Flutter app - public, no-login tracking lookup by
 * tracking_code (DHL-style), mirrors the web
 * Fret\ShipmentController::trackPublic.
 */
class ShipmentController extends Controller
{
    public function track(string $code): JsonResponse
    {
        $shipment = Shipment::query()
            ->where('tracking_code', strtoupper(trim($code)))
            ->first([
                'id', 'tracking_code', 'origin_city', 'origin_country',
                'destination_city', 'destination_country', 'mode', 'status',
                'weight_kg', 'picked_up_at', 'delivered_at', 'created_at',
            ]);

        abort_unless($shipment, 404, 'Aucun envoi trouve pour ce code de suivi.');

        return response()->json($shipment);
    }
}
