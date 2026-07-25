<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\Voyage\TravelOffer;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public landing page - mirrors the "Le monde a portee de main" mockup:
 * hero + 6 service tiles + current offers + trust badges.
 */
class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'categories' => ServiceCategory::query()->orderBy('position')->get(),
            'featuredOffers' => TravelOffer::query()
                ->active()
                ->featured()
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }
}
