<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use Inertia\Inertia;
use Inertia\Response;

// Public catalog of freight/shipping service offers published by partners
// (carriers) - distinct from ShipmentController, which tracks a customer's
// own already-created shipments.
class FreightOfferController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Fret/Index', [
            'offers' => FreightOffer::query()->active()->latest()->paginate(9),
        ]);
    }

    public function show(FreightOffer $freightOffer): Response
    {
        abort_unless($freightOffer->is_active && $freightOffer->status === 'published', 404);

        return Inertia::render('Fret/Show', ['offer' => $freightOffer]);
    }
}
