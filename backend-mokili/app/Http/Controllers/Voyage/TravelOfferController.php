<?php

namespace App\Http\Controllers\Voyage;

use App\Http\Controllers\Controller;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOYAGE - pilot module. Public catalogue: list + detail pages.
 */
class TravelOfferController extends Controller
{
    public function index(Request $request): Response
    {
        $offers = TravelOffer::query()
            ->active()
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('is_featured')
            ->orderBy('departure_at')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Voyage/Index', [
            'offers' => $offers,
            'filters' => $request->only(['destination', 'type']),
        ]);
    }

    public function show(TravelOffer $travelOffer): Response
    {
        return Inertia::render('Voyage/Show', [
            'offer' => $travelOffer,
        ]);
    }
}
