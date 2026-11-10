<?php

namespace App\Http\Controllers\Logement;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Logement\LodgingListing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LOGEMENT - recherche facon Booking.com : destination + dates + occupants
 * (adultes, enfants, chambres), filtres (budget, type, chambres,
 * equipements), tri, destinations en vogue, fiche detaillee avec
 * disponibilites. Les dates ne sont pas encore controlees contre un
 * calendrier par nuit : un logement deja reserve sur ces dates est
 * simplement signale "Deja reserve sur vos dates" s'il chevauche une
 * reservation payee.
 */
class LodgingListingController extends Controller
{
    public const AMENITIES = [
        'wifi' => 'Wi-Fi gratuit', 'climatisation' => 'Climatisation', 'parking' => 'Parking', 'piscine' => 'Piscine',
        'petit-dejeuner' => 'Petit-dejeuner', 'salle de sport' => 'Salle de sport', 'jardin' => 'Jardin',
        'ascenseur' => 'Ascenseur', 'cuisine' => 'Cuisine', 'television' => 'Television', 'navette' => 'Navette aeroport',
    ];

    public const SORTS = [
        'populaires' => 'Nos meilleurs choix',
        'prix_asc' => 'Prix (le plus bas)',
        'prix_desc' => 'Prix (le plus eleve)',
        'capacite' => 'Les plus spacieux',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->only(['city', 'check_in', 'check_out', 'guests', 'adults', 'children', 'rooms', 'price_min', 'price_max', 'type', 'bedrooms', 'amenities', 'sort']);
        $guests = $request->filled('adults') || $request->filled('children')
            ? max(1, $request->integer('adults') + $request->integer('children'))
            : ($request->filled('guests') ? $request->integer('guests') : null);
        $amenities = array_values(array_intersect(array_keys(self::AMENITIES), (array) $request->input('amenities', [])));
        $types = array_values(array_filter((array) $request->input('type', [])));

        $base = LodgingListing::query()->active()
            ->when($request->filled('city'), fn ($q) => $q->where(fn ($w) => $w
                ->where('city', 'like', '%'.$request->string('city').'%')
                ->orWhere('title', 'like', '%'.$request->string('city').'%')
                ->orWhere('address', 'like', '%'.$request->string('city').'%')))
            ->when($guests, fn ($q) => $q->where('max_guests', '>=', $guests))
            ->when($request->integer('rooms') > 1, fn ($q) => $q->where('bedrooms', '>=', $request->integer('rooms')));

        // Compteurs des filtres (avant budget/type/equipements), comme Booking.
        $pool = (clone $base)->get(['id', 'property_type', 'amenities', 'price_per_night']);
        $facets = [
            'types' => collect(config('catalogue.logement.property_types'))->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'count' => $pool->where('property_type', $key)->count(),
            ])->values(),
            'amenities' => collect(self::AMENITIES)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'count' => $pool->filter(fn ($l) => in_array($key, $l->amenities ?? [], true))->count(),
            ])->filter(fn ($a) => $a['count'] > 0)->values(),
            'price_max' => (int) ($pool->max('price_per_night') ?? 0),
        ];

        $listings = (clone $base)
            ->when($types, fn ($q) => $q->whereIn('property_type', $types))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->integer('bedrooms')))
            ->when($request->filled('price_min'), fn ($q) => $q->where('price_per_night', '>=', $request->float('price_min')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('price_per_night', '<=', $request->float('price_max')))
            ->when($amenities, function ($q) use ($amenities) {
                foreach ($amenities as $a) {
                    $q->where('amenities', 'like', '%"'.$a.'"%');
                }
            })
            ->tap(fn ($q) => match ($request->input('sort')) {
                'prix_asc' => $q->orderBy('price_per_night'),
                'prix_desc' => $q->orderByDesc('price_per_night'),
                'capacite' => $q->orderByDesc('max_guests')->orderByDesc('bedrooms'),
                default => $q->orderByDesc('discount_percent')->latest(),
            })
            ->paginate(10)
            ->withQueryString();

        $busy = $this->busyIds($listings->getCollection()->pluck('id')->all(), $request->input('check_in'), $request->input('check_out'));
        $listings->getCollection()->transform(fn (LodgingListing $l) => $l->setAttribute('booked_on_dates', in_array($l->id, $busy, true)));

        return Inertia::render('Logement/Index', [
            'listings' => $listings,
            'filters' => (object) $filters,
            'propertyTypes' => config('catalogue.logement.property_types'),
            'facets' => $facets,
            'sorts' => self::SORTS,
            'popularCities' => $this->popularCities(),
        ]);
    }

    public function show(Request $request, LodgingListing $lodgingListing): Response
    {
        abort_unless($lodgingListing->is_active && $lodgingListing->status === 'published', 404);
        $search = $request->only(['check_in', 'check_out', 'guests', 'adults', 'children', 'rooms']);

        return Inertia::render('Logement/Show', [
            'listing' => $lodgingListing,
            'search' => $search,
            'amenityLabels' => self::AMENITIES,
            'bookedOnDates' => in_array($lodgingListing->id, $this->busyIds([$lodgingListing->id], $search['check_in'] ?? null, $search['check_out'] ?? null), true),
            'similar' => LodgingListing::query()->active()->where('id', '!=', $lodgingListing->id)
                ->orderByRaw('case when city = ? then 0 else 1 end', [$lodgingListing->city])
                ->latest()->take(4)->get(),
        ]);
    }

    /** Logements ayant une reservation payee / en attente qui chevauche les dates. */
    protected function busyIds(array $ids, ?string $in, ?string $out): array
    {
        if (! $ids || ! $in || ! $out || $out <= $in) {
            return [];
        }

        return Booking::query()
            ->where('bookable_type', LodgingListing::class)->whereIn('bookable_id', $ids)
            ->whereIn('status', ['awaiting_payment', 'confirmed', 'paid', 'completed'])
            ->where('starts_at', '<', $out)->where('ends_at', '>', $in)
            ->pluck('bookable_id')->unique()->all();
    }

    /** "Destinations en vogue" : villes les plus fournies, avec une photo. */
    protected function popularCities(): array
    {
        return LodgingListing::query()->active()
            ->selectRaw('city, country, count(*) as total, min(price_per_night) as from_price')
            ->groupBy('city', 'country')->orderByDesc('total')->take(6)->get()
            ->map(fn ($c) => [
                'city' => $c->city, 'country' => $c->country, 'total' => (int) $c->total, 'from_price' => (float) $c->from_price,
                'image' => LodgingListing::query()->active()->where('city', $c->city)->whereNotNull('image')->first()?->image_url,
            ])->all();
    }
}
