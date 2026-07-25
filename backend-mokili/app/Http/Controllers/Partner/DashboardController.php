<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Partner space landing page (role:partner). A partner manages their
 * own listings across the 4 "ownable" modules - Logement, Voiture,
 * Divertissement, Marketplace (Voyage is centrally managed by admin/
 * agent staff, Fret is a client-initiated shipment, not a listing).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Partner/Dashboard', [
            'counts' => [
                'logement' => $user->lodgingListings()->count(),
                'voiture' => $user->vehicles()->count(),
                'divertissement' => $user->events()->count(),
                'marketplace' => $user->products()->count(),
            ],
        ]);
    }
}
