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
            ->when($request->filled('country'), fn ($q) => $q->where('country', strtoupper((string) $request->input('country'))))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('is_featured')
            ->orderByDesc('stars')
            ->orderBy('name')
            ->paginate(50));
    }

    /**
     * ANNUAIRE (app mobile) : GET /api/annuaire?q=&pays=&ville=&domaine=
     * -> entreprises + listes des domaines, pays et villes pour les filtres.
     */
    public function directory(Request $request): JsonResponse
    {
        $f = \App\Http\Controllers\Guide\DirectoryController::filters($request);
        $base = Business::query()->active();

        return response()->json([
            'businesses' => \App\Http\Controllers\Guide\DirectoryController::search($f)
                ->orderByDesc('is_featured')->orderBy('name')->paginate(30)->withQueryString(),
            'filters' => $f,
            'domains' => \App\Http\Controllers\Guide\DirectoryController::domains(),
            'countries' => \App\Http\Controllers\Guide\DirectoryController::countryOptions(
                (clone $base)->selectRaw('country, count(*) as n')->groupBy('country')->pluck('n', 'country')->all()
            ),
            'cities' => (clone $base)->when($f['pays'] !== '', fn ($q) => $q->where('country', $f['pays']))
                ->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    /** ANNUAIRE (app mobile) : inscription gratuite, publiee apres validation. */
    public function register(Request $request): JsonResponse
    {
        if ($request->filled('fax')) {
            return response()->json(['message' => 'ok'], 201);
        }
        $data = \App\Http\Controllers\Guide\DirectoryController::validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('partenaires', 'public');
        }
        $business = Business::create($data);

        return response()->json([
            'message' => 'Merci ! Votre entreprise apparaitra dans l\'annuaire apres verification par MOKILI TOUR.',
            'id' => $business->id,
        ], 201);
    }

    public function show(Business $business): JsonResponse
    {
        abort_unless($business->is_active, 404);

        // booking : "visite" (site touristique), "rdv" (garage / atelier) ou null.
        return response()->json($business->toArray() + [
            'booking' => VisitRequestController::isBookable($business) ? (VisitRequestController::isGarage($business) ? 'rdv' : 'visite') : null,
            'mechanic_services' => VisitRequestController::isGarage($business) ? \App\Services\Voiture\GarageSearch::servicesFor($business) : [],
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
