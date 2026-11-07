<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Catalogue\TourismeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Libelles des rubriques et filtres (config/catalogue.php) pour l'app mobile. */
class CatalogueController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(config('catalogue'));
    }

    /** GET /api/tourisme?tab=concerts|cinema|parcs|night_club|restaurant|bar_lounge|visite&city= */
    public function tourisme(Request $request, TourismeCatalog $catalog): JsonResponse
    {
        $tab = $catalog->resolveTab($request->string('tab')->toString(), $request->string('category')->toString()) ?? 'concerts';
        $result = $catalog->items($tab, $request->only('city', 'type'), 20);

        return response()->json([
            'tab' => $tab,
            'label' => $catalog->tabs()[$tab]['label'],
            'source' => $result['source'],
        ] + $result['items']->toArray());
    }
}
