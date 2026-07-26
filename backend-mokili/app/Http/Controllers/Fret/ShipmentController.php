<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\Shipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// FRET module (skeleton) - suivi de colis pour l'utilisateur connecte.
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
}
