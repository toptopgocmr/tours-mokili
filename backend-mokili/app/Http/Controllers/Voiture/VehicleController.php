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
    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString();
        $types = config('catalogue.voiture.offer_types');
        $type = isset($types[$type]) ? $type : '';

        return Inertia::render('Voiture/Index', [
            'vehicles' => Vehicle::query()
                ->active()
                ->when($type, fn ($q) => $q->where('offer_type', $type))
                ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('title', 'like', '%'.$request->string('q').'%')
                    ->orWhere('brand', 'like', '%'.$request->string('q').'%')
                    ->orWhere('model', 'like', '%'.$request->string('q').'%')))
                // Location : seulement les vehicules libres sur les dates demandees.
                ->when($type === 'location' && $request->filled('start') && $request->filled('end'), function ($q) use ($request) {
                    $start = \Carbon\Carbon::parse($request->input('start'))->startOfDay();
                    $end = \Carbon\Carbon::parse($request->input('end'))->endOfDay();
                    $q->whereNotIn('id', \App\Models\Booking::query()->where('bookable_type', Vehicle::class)
                        ->where(fn ($s) => $s->whereIn('status', ['confirmed', 'paid', 'completed'])
                            ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '>=', now()->subMinutes(\App\Services\Voiture\VehicleAvailability::HOLD_MINUTES))))
                        ->where('starts_at', '<=', $end)->where('ends_at', '>=', $start)->select('bookable_id'));
                })
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            // Mecanique : les garages partenaires en plus des annonces.
            'garages' => $type === 'mecanique'
                ? Business::query()->active()->where('category', 'garage')
                    ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                    ->orderByDesc('is_featured')->orderBy('name')->limit(24)->get()
                : [],
            'filters' => (object) ($request->only('city', 'q', 'start', 'end') + ['type' => $type]),
            'offerTypes' => $types,
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);

        return Inertia::render('Voiture/Show', [
            'vehicle' => $vehicle,
            'busyDays' => $vehicle->offer_type === 'location' ? \App\Services\Voiture\VehicleAvailability::busyDays($vehicle) : [],
            'pickupModes' => \App\Http\Controllers\Voiture\RentalController::PICKUP,
            'search' => request()->only(['start', 'end']),
            'similar' => Vehicle::query()->active()->where('offer_type', $vehicle->offer_type)
                ->whereKeyNot($vehicle->id)->latest()->limit(4)->get(),
        ]);
    }
}
