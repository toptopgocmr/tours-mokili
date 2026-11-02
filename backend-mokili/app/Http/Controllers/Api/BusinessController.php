<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
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

        return response()->json($business);
    }
}
