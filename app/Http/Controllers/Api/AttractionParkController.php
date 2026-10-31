<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParkBookingRequest;
use App\Models\Loisirs\AttractionPark;
use App\Services\Loisirs\ParkBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** JSON API (app Flutter) - LOISIRS : parcs d'attractions du Congo. */
class AttractionParkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $parks = AttractionPark::query()
            ->active()
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(20);

        return response()->json($parks);
    }

    public function categories(): JsonResponse
    {
        return response()->json(collect(AttractionPark::CATEGORIES)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values());
    }

    public function show(AttractionPark $attractionPark): JsonResponse
    {
        abort_unless($attractionPark->is_active, 404);

        return response()->json($attractionPark);
    }

    public function book(StoreParkBookingRequest $request, AttractionPark $attractionPark, ParkBookingService $service): JsonResponse
    {
        $booking = $service->create($attractionPark, $request->user()->id, $request->validated());

        return response()->json($booking, 201);
    }
}
