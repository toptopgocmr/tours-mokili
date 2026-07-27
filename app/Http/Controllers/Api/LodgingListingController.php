<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Logement\LodgingListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for the Flutter app - LOGEMENT module (follows the Voyage
 * pilot module pattern, see TravelOfferController).
 */
class LodgingListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listings = LodgingListing::query()
            ->active()
            ->when($request->filled('city'), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->filled('guests'), fn ($q) => $q->where(
                'max_guests', '>=', $request->integer('guests')
            ))
            ->latest()
            ->paginate(10);

        return response()->json($listings);
    }

    public function show(LodgingListing $lodgingListing): JsonResponse
    {
        abort_unless($lodgingListing->is_active && $lodgingListing->status === 'published', 404);

        return response()->json($lodgingListing);
    }

    public function book(StoreBookingRequest $request, LodgingListing $lodgingListing): JsonResponse
    {
        $booking = DB::transaction(function () use ($request, $lodgingListing) {
            $nights = $request->integer('quantity');
            $unitPrice = $lodgingListing->price_per_night;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => LodgingListing::class,
                'bookable_id' => $lodgingListing->id,
                'quantity' => $nights,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $nights,
                'currency' => $lodgingListing->currency,
                'starts_at' => $request->input('starts_at'),
                'ends_at' => $request->input('ends_at'),
                'status' => 'awaiting_payment',
                'meta' => ['city' => $lodgingListing->city],
                'notes' => $request->string('notes'),
            ]);
        });

        return response()->json($booking, 201);
    }
}
