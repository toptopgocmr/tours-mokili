<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\Shipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// FRET module - suivi de colis pour l'utilisateur connecte (mes-envois),
// et lookup public par code de suivi sans connexion, a la DHL.
class ShipmentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Fret/Suivi/Index', [
            'shipments' => $request->user()
                ? Shipment::query()->where('user_id', $request->user()->id)->latest()->paginate(9)
                : ['data' => []],
        ]);
    }

    public function show(Shipment $shipment): Response
    {
        return Inertia::render('Fret/Suivi/Show', ['shipment' => $shipment]);
    }

    /**
     * Public, no-login tracking lookup by tracking_code - mirrors DHL's
     * homepage tracking box. Only public-safe fields are exposed (no
     * user_id/owner info).
     */
    public function trackPublic(Request $request, ?string $code = null): Response
    {
        $shipment = null;
        $notFound = false;

        if ($code) {
            $shipment = Shipment::query()
                ->where('tracking_code', strtoupper(trim($code)))
                ->first(['tracking_code', 'origin_city', 'origin_country', 'destination_city', 'destination_country', 'mode', 'status', 'weight_kg', 'picked_up_at', 'delivered_at', 'created_at']);
            $notFound = ! $shipment;
        }

        return Inertia::render('Fret/Suivi/Track', [
            'code' => $code,
            'shipment' => $shipment,
            'notFound' => $notFound,
        ]);
    }
}
