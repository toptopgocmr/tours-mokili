<?php

namespace App\Http\Controllers\Voiture;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// VOITURE : location, vente et mecanique (garages et prestations).
class VehicleController extends Controller
{
    public const SORTS = [
        'recent' => 'Les plus recentes',
        'prix_asc' => 'Prix croissant',
        'prix_desc' => 'Prix decroissant',
        'km_asc' => 'Kilometrage le plus bas',
        'annee_desc' => 'Annee la plus recente',
    ];

    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString();
        $types = config('catalogue.voiture.offer_types');
        $type = isset($types[$type]) ? $type : '';
        $priceCol = $type === 'location' ? 'price_per_day' : 'sale_price';

        $base = Vehicle::query()->active()->when($type, fn ($q) => $q->where('offer_type', $type));
        $query = (clone $base)
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('brand', 'like', '%'.$request->string('q').'%')
                ->orWhere('model', 'like', '%'.$request->string('q').'%')))
            // Voitures / Utilitaires (facon Europcar / Sixt).
            ->when($request->input('vehicule') === 'utilitaires', fn ($q) => $q->where('category', 'utilitaire'))
            ->when($request->input('vehicule') === 'voitures', fn ($q) => $q->where(fn ($w) => $w->whereNull('category')->orWhere('category', '!=', 'utilitaire')))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->string('brand')))
            // Favoris (ids enregistres dans le navigateur).
            ->when($request->filled('ids'), fn ($q) => $q->whereIn('id', collect(explode(',', (string) $request->input('ids')))->map(fn ($i) => (int) $i)->filter()))
            ->when($request->filled('model'), fn ($q) => $q->where('model', 'like', '%'.$request->string('model').'%'))
            ->when($request->filled('color'), fn ($q) => $q->where('color', $request->string('color')))
            ->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->string('condition')))
            ->when($request->filled('doors'), fn ($q) => $q->where('doors', $request->integer('doors')))
            ->when($request->boolean('first_owner'), fn ($q) => $q->where('first_owner', true))
            ->when($request->filled('fuel'), fn ($q) => $q->where('fuel', $request->string('fuel')))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->string('transmission')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('seats'), fn ($q) => $q->where('seats', '>=', $request->integer('seats')))
            ->when($request->filled('price_min'), fn ($q) => $q->where($priceCol, '>=', $request->float('price_min')))
            ->when($request->filled('price_max'), fn ($q) => $q->where($priceCol, '<=', $request->float('price_max')))
            ->when($request->filled('year_min'), fn ($q) => $q->where('year', '>=', $request->integer('year_min')))
            ->when($request->filled('km_max'), fn ($q) => $q->where('mileage_km', '<=', $request->integer('km_max')))
            // Location : seulement les vehicules libres sur les dates demandees.
            ->when($type === 'location' && $request->filled('start') && $request->filled('end'), function ($q) use ($request) {
                $start = \Carbon\Carbon::parse($request->input('start'))->startOfDay();
                $end = \Carbon\Carbon::parse($request->input('end'))->endOfDay();
                $q->whereNotIn('id', \App\Models\Booking::query()->where('bookable_type', Vehicle::class)
                    ->where(fn ($s) => $s->whereIn('status', ['confirmed', 'paid', 'completed'])
                        ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '>=', now()->subMinutes(\App\Services\Voiture\VehicleAvailability::HOLD_MINUTES))))
                    ->where('starts_at', '<=', $end)->where('ends_at', '>=', $start)->select('bookable_id'));
            });

        match ($request->input('sort')) {
            'prix_asc' => $query->orderByRaw("$priceCol is null")->orderBy($priceCol),
            'prix_desc' => $query->orderByDesc($priceCol),
            'km_asc' => $query->orderByRaw('mileage_km is null')->orderBy('mileage_km'),
            'annee_desc' => $query->orderByDesc('year'),
            default => $query->latest(),
        };

        $pool = (clone $base)->get(['brand', 'fuel', 'transmission', 'category', 'color', 'condition']);
        $count = fn ($col) => $pool->pluck($col)->filter()->countBy()->sortKeys()->map(fn ($n, $k) => ['key' => $k, 'count' => $n])->values();

        $page = $query->paginate(12)->withQueryString();
        // Vente : vehicules deja reserves par un acompte.
        $reserved = $type === 'vente' ? \App\Models\Booking::query()->where('bookable_type', Vehicle::class)
            ->whereIn('bookable_id', $page->getCollection()->pluck('id'))->where('meta->kind', 'acompte_vente')
            ->where(fn ($q) => $q->whereIn('status', ['confirmed', 'paid', 'completed'])
                ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '>=', now()->subMinutes(SaleController::HOLD_MINUTES))))
            ->pluck('bookable_id')->unique()->values() : [];

        return Inertia::render('Voiture/Index', [
            'vehicles' => $page,
            'reservedIds' => $reserved,
            'finance' => config('catalogue.voiture.finance'),
            // Mecanique : les garages partenaires en plus des annonces.
            'garages' => $type === 'mecanique'
                ? Business::query()->active()->where('category', 'garage')
                    ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(24)->get()
                : [],
            'filters' => (object) ($request->only('city', 'q', 'start', 'end', 'start_time', 'end_time', 'vehicule', 'brand', 'model', 'color', 'condition', 'doors', 'first_owner', 'fuel', 'transmission', 'category', 'seats', 'price_min', 'price_max', 'year_min', 'km_max', 'sort', 'age', 'return_city', 'ids') + ['type' => $type]),
            'offerTypes' => $types,
            'facets' => ['brands' => $count('brand'), 'fuels' => $count('fuel'), 'transmissions' => $count('transmission'), 'categories' => $count('category'), 'colors' => $count('color'), 'conditions' => $count('condition')],
            'sorts' => self::SORTS,
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);
        // Compteur de vues (une fois par session).
        $seen = session('vehicles_seen', []);
        if (! in_array($vehicle->id, $seen, true)) {
            $vehicle->increment('views');
            session(['vehicles_seen' => [...$seen, $vehicle->id]]);
        }

        return Inertia::render('Voiture/Show', [
            'vehicle' => $vehicle,
            'busyDays' => $vehicle->offer_type === 'location' ? \App\Services\Voiture\VehicleAvailability::busyDays($vehicle) : [],
            'pickupModes' => \App\Http\Controllers\Voiture\RentalController::PICKUP,
            'idTypes' => \App\Http\Controllers\Voiture\RentalController::ID_TYPES,
            'sale' => $vehicle->offer_type === 'vente' ? [
                'reserved' => SaleController::isReserved($vehicle),
                'deposit' => SaleController::deposit($vehicle),
                'finance' => config('catalogue.voiture.finance'),
                'seller' => $vehicle->owner ? ['name' => $vehicle->owner->company_name ?: $vehicle->owner->name, 'verified' => (bool) $vehicle->owner->partner_verified_at, 'since' => optional($vehicle->owner->created_at)->year] : null,
            ] : null,
            'search' => request()->only(['start', 'end', 'start_time', 'end_time', 'age', 'return_city']),
            'similar' => Vehicle::query()->active()->where('offer_type', $vehicle->offer_type)
                ->whereKeyNot($vehicle->id)->latest()->limit(4)->get(),
        ]);
    }
}
