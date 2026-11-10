<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Voiture\Vehicle;
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
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->where(
                'category', $request->string('category')
            ))
            // Location, vente, mecanique.
            ->when($request->string('offer_type')->isNotEmpty(), fn ($q) => $q->where('offer_type', $request->string('offer_type')))
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($vehicles);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle);
    }

    public function book(StoreBookingRequest $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);

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
