<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use Inertia\Inertia;
use Inertia\Response;

// Fiche publique d'une societe partenaire (site web, Inertia).
class BusinessController extends Controller
{
    public function show(Business $business): Response
    {
        abort_unless($business->is_active, 404);

        return Inertia::render('Partners/Show', [
            'business' => $business,
            'similar' => Business::query()->active()
                ->where('category', $business->category)
                ->where('id', '!=', $business->id)
                ->orderByRaw('city = ? desc', [$business->city])
                ->orderByDesc('is_featured')
                ->inRandomOrder()
                ->limit(4)
                ->get(),
            'formulas' => \App\Models\Guide\VisitRequest::FORMULAS,
        ]);
    }
}
