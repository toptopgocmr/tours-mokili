<?php

namespace App\Http\Controllers\Voiture;

use App\Http\Controllers\Controller;
use App\Models\Voiture\Vehicle;
use Inertia\Inertia;
use Inertia\Response;

// VOITURE module (skeleton).
class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Voiture/Index', [
            'vehicles' => Vehicle::query()->active()->latest()->paginate(9),
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);

        return Inertia::render('Voiture/Show', ['vehicle' => $vehicle]);
    }
}
