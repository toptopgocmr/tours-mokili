<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Logement\LodgingListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - LOGEMENT module (style Booking.com:
 * recherche par ville, liste avec prix/nuit, fiche detail, reservation).
 */
class LodgingListingController extends Controller
{
    use BooksListings;

    public function index(Request $request): JsonResponse
    {
        $listings = LodgingListing::query()
            ->active()
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->integer('guests') > 0, fn ($q) => $q->where(
                'max_guests', '>=', $request->integer('guests')
            ))
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($listings);
    }

    public function show(LodgingListing $listing): JsonResponse
    {
        return response()->json($listing);
    }

    public function book(StoreBookingRequest $request, LodgingListing $listing): JsonResponse
    {
        abort_unless($listing->is_active && $listing->status === 'published', 404);

        $booking = $this->createBooking(
            $request,
            $listing,
            LodgingListing::class,
            (float) $listing->price_per_night,
            $listing->currency,
        );

        return response()->json($booking, 201);
    }
}
