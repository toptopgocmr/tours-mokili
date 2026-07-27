<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for the Flutter app - VOITURE module (follows the Voyage
 * pilot module pattern, see TravelOfferController).
 */
class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->active()
            ->when($request->filled('city'), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()
            ->paginate(10);

        return response()->json($vehicles);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published', 404);

        return response()->json($vehicle);
    }

    public function book(StoreBookingRequest $request, Vehicle $vehicle): JsonResponse
    {
        $booking = DB::transaction(function () use ($request, $vehicle) {
            $days = $request->integer('quantity');
            $unitPrice = $vehicle->price_per_day;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => Vehicle::class,
                'bookable_id' => $vehicle->id,
                'quantity' => $days,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $days,
                'currency' => $vehicle->currency,
                'starts_at' => $request->input('starts_at'),
                'ends_at' => $request->input('ends_at'),
                'status' => 'awaiting_payment',
                'meta' => ['city' => $vehicle->city],
                'notes' => $request->string('notes'),
            ]);
        });

        return response()->json($booking, 201);
    }
}
