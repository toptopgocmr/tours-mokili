<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Voiture\Vehicle;
use App\Services\Voiture\GarageSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - VOITURE module (location de vehicules).
 */
class VehicleController extends Controller
{
    use BooksListings;

    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->active()
            ->when(! config('catalogue.voiture.show_demo'), fn ($q) => $q->where(fn ($w) => $w->whereNull('is_demo')->orWhere('is_demo', false)))
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->where(
                'category', $request->string('category')
            ))
            // Location, vente, mecanique.
            ->when($request->string('offer_type')->isNotEmpty(), fn ($q) => $q->where('offer_type', $request->string('offer_type')))
            ->when($request->string('brand')->isNotEmpty(), fn ($q) => $q->where('brand', $request->string('brand')))
            ->when($request->string('fuel')->isNotEmpty(), fn ($q) => $q->where('fuel', $request->string('fuel')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('sale_price', '<=', $request->float('price_max')))
            ->when($request->string('q')->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('brand', 'like', '%'.$request->string('q').'%')
                ->orWhere('model', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('created_at')
            ->paginate(min(50, max(10, $request->integer('per_page', 20))));

        return response()->json($vehicles);
    }

    /** Listes pour l'app : marques en vente (avec nombre d'annonces), carburants, prestations mecanique. */
    public function meta(): JsonResponse
    {
        $brands = Vehicle::query()->active()->where('offer_type', 'vente')
            ->when(! config('catalogue.voiture.show_demo'), fn ($q) => $q->where(fn ($w) => $w->whereNull('is_demo')->orWhere('is_demo', false)))
            ->selectRaw('brand, count(*) as count')->groupBy('brand')->orderBy('brand')->get()
            ->filter(fn ($b) => $b->brand)->map(fn ($b) => ['key' => $b->brand, 'count' => (int) $b->count])->values();

        return response()->json([
            'brands' => $brands,
            'fuels' => config('catalogue.voiture.fuels'),
            'mechanic_services' => config('catalogue.voiture.mechanic_services'),
        ]);
    }

    /** Voiture > Mecanique : garages et depanneurs (?city=&q=prestation). */
    public function garages(Request $request): JsonResponse
    {
        return response()->json([
            'services' => config('catalogue.voiture.mechanic_services'),
            'data' => GarageSearch::query($request->input('city'), $request->input('q'))->limit(60)->get(),
        ]);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);

        // Vente : vendeurs / concessionnaires partenaires de la marque.
        return response()->json($vehicle->toArray() + [
            'dealers' => $vehicle->offer_type === 'vente' ? GarageSearch::dealersFor($vehicle->brand, $vehicle->city) : [],
        ]);
    }

    public function book(StoreBookingRequest $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);
        if ($vehicle->offer_type === 'location' && $request->filled('starts_at')) {
            $start = \Carbon\Carbon::parse($request->input('starts_at'));
            $end = \Carbon\Carbon::parse($request->input('ends_at') ?: $request->input('starts_at'));
            abort_unless(\App\Services\Voiture\VehicleAvailability::isAvailable($vehicle, $start, $end), 422, 'Ce vehicule est deja reserve sur ces dates.');
        }

        $booking = $this->createBooking(
            $request,
            $vehicle,
            Vehicle::class,
            $vehicle->discountedDayPrice(),
            $vehicle->currency,
        );

        return response()->json($booking, 201);
    }
}
