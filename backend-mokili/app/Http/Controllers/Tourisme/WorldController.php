<?php

namespace App\Http\Controllers\Tourisme;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Models\Loisirs\AttractionPark;
use App\Services\Tourisme\WorldPlaces;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** TOURISME > Monde : sites touristiques, musees, parcs, loisirs de n'importe quelle ville. */
class WorldController extends Controller
{
    public function index(Request $request, WorldPlaces $world): Response
    {
        $city = trim((string) $request->query('ville', 'Brazzaville')) ?: 'Brazzaville';
        $result = $world->search($city);
        $name = $result['city']['name'] ?? $city;

        return Inertia::render('Tourisme/Monde', [
            'ville' => $city,
            'categorie' => array_key_exists((string) $request->query('categorie'), WorldPlaces::CATEGORIES) ? $request->query('categorie') : '',
            'result' => $result,
            'categories' => WorldPlaces::CATEGORIES,
            'cities' => WorldPlaces::CITIES,
            // Adresses partenaires MOKILI TOUR dans cette ville (en premier).
            'partners' => Business::query()->active()->whereIn('category', ['site_touristique', 'restaurant', 'bar_lounge', 'night_club'])
                ->where('city', 'like', '%'.$name.'%')->orderByDesc('is_featured')->limit(8)->get(),
            'parks' => AttractionPark::query()->active()->where('city', 'like', '%'.$name.'%')->orderByDesc('is_featured')->limit(6)->get(),
        ]);
    }
}
