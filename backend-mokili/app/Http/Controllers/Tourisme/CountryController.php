<?php

namespace App\Http\Controllers\Tourisme;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Models\Loisirs\AttractionPark;
use App\Services\Catalogue\TourismeCatalog;
use App\Services\Tourisme\WorldPlaces;
use App\Support\TourismeCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TOURISME : page "Choisissez un pays" (avant la page des sorties), puis
 * la page de chaque pays (ses villes et leurs sites touristiques).
 */
class CountryController extends Controller
{
    public function index(Request $request, TourismeCatalog $catalog): Response|RedirectResponse
    {
        // Anciennes adresses /tourisme?tab=... : on garde la redirection.
        if ($request->getQueryString()) {
            return redirect('/divertissement?'.$request->getQueryString());
        }

        return Inertia::render('Tourisme/Pays', [
            'countries' => TourismeCountries::all(),
            'continents' => TourismeCountries::CONTINENTS,
            // Nombre de sorties et sites MOKILI TOUR au Congo.
            'localCount' => $this->localCount($catalog),
        ]);
    }

    public function show(Request $request, string $slug, WorldPlaces $world): Response|RedirectResponse
    {
        if ($slug === TourismeCountries::LOCAL) {
            return redirect('/divertissement');
        }
        $country = TourismeCountries::find($slug);
        abort_unless($country, 404);

        $city = in_array($request->query('ville'), $country['cities'], true) ? $request->query('ville') : $country['cities'][0];
        $result = $world->search($city);
        $name = $result['city']['name'] ?? $city;

        return Inertia::render('Tourisme/Monde', [
            'country' => $country,
            'ville' => $city,
            'categorie' => array_key_exists((string) $request->query('categorie'), WorldPlaces::CATEGORIES) ? $request->query('categorie') : '',
            'result' => $result,
            'categories' => WorldPlaces::CATEGORIES,
            'cities' => [$country['name'] => $country['cities']],
            'partners' => Business::query()->active()->whereIn('category', ['site_touristique', 'restaurant', 'bar_lounge', 'night_club'])
                ->where('city', 'like', '%'.$name.'%')->orderByDesc('is_featured')->limit(8)->get(),
            'parks' => AttractionPark::query()->active()->where('city', 'like', '%'.$name.'%')->orderByDesc('is_featured')->limit(6)->get(),
        ]);
    }

    /** API (app mobile) : la liste des pays. */
    public function api(TourismeCatalog $catalog): JsonResponse
    {
        return response()->json([
            'continents' => TourismeCountries::CONTINENTS,
            'countries' => TourismeCountries::all(),
            'local_count' => $this->localCount($catalog),
        ]);
    }

    private function localCount(TourismeCatalog $catalog): int
    {
        try {
            return $catalog->cards(null, ['city' => null, 'type' => null, 'q' => null])->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
