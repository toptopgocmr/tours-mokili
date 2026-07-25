<?php

namespace App\Http\Controllers\Logement;

use App\Http\Controllers\Controller;
use App\Models\Logement\LodgingListing;
use Inertia\Inertia;
use Inertia\Response;

// LOGEMENT module (skeleton) - same shape as Voyage\TravelOfferController,
// ready to be extended with booking/checkout wiring.
class LodgingListingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Logement/Index', [
            'listings' => LodgingListing::query()->active()->latest()->paginate(9),
        ]);
    }

    public function show(LodgingListing $lodgingListing): Response
    {
        return Inertia::render('Logement/Show', ['listing' => $lodgingListing]);
    }
}
