<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guide\VisitRequestController;
use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** JSON API (app Flutter + site) - societes partenaires par rubrique. */
class BusinessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Business::query()
            ->active()
            ->when($request->filled('module'), fn ($q) => $q->forModule((string) $request->input('module')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('is_featured')
            ->orderByDesc('stars')
            ->orderBy('name')
            ->paginate(50));
    }

    public function show(Business $business): JsonResponse
    {
        abort_unless($business->is_active, 404);

        // booking : "visite" (site touristique), "rdv" (garage / atelier) ou null.
        return response()->json($business->toArray() + [
            'booking' => VisitRequestController::isBookable($business) ? (VisitRequestController::isGarage($business) ? 'rdv' : 'visite') : null,
            'mechanic_services' => VisitRequestController::isGarage($business) ? config('catalogue.voiture.mechanic_services') : [],
            'formulas' => VisitRequest::FORMULAS,
        ]);
    }

    /** App mobile : "Reserver ma visite" / "Prendre rendez-vous" (sans engagement). */
    public function book(Request $request, Business $business): JsonResponse
    {
        $r = VisitRequestController::createFor($request, $business);

        return response()->json([
            'id' => $r->id,
            'message' => VisitRequestController::isGarage($business)
                ? 'Demande de rendez-vous envoyee. Nous vous rappelons sous 24 h pour confirmer.'
                : 'Demande de visite envoyee. L\'equipe MOKILI TOUR vous rappelle pour confirmer.',
        ], 201);
    }
}
