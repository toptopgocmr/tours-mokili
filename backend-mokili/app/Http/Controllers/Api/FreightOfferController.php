<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFreightBookingRequest;
use App\Models\Fret\FreightOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - FRET module (style DHL: offres de
 * transporteurs par trajet/mode, prix au kg, reservation).
 * See ShipmentController for the "suivi de colis" tracking endpoint.
 */
class FreightOfferController extends Controller
{
    use BooksListings;

    public function index(Request $request): JsonResponse
    {
        $offers = FreightOffer::query()
            ->active()
            ->when($request->string('origin')->isNotEmpty(), fn ($q) => $q->where(
                'origin_city', 'like', '%'.$request->string('origin').'%'
            ))
            ->when($request->string('destination')->isNotEmpty(), fn ($q) => $q->where(
                'destination_city', 'like', '%'.$request->string('destination').'%'
            ))
            ->when($request->string('mode')->isNotEmpty(), fn ($q) => $q->where(
                'mode', $request->string('mode')
            ))
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($offers);
    }

    public function show(FreightOffer $offer): JsonResponse
    {
        return response()->json($offer);
    }

    /**
     * "quantity" here is the parcel/cargo weight in kg - see
     * StoreFreightBookingRequest (integer 1-20000, unlike the generic
     * 1-20 "quantity" used by every other module).
     */
    public function book(StoreFreightBookingRequest $request, FreightOffer $offer): JsonResponse
    {
        abort_unless($offer->is_active && $offer->status === 'published', 404);

        $booking = $this->createBooking(
            $request,
            $offer,
            FreightOffer::class,
            (float) $offer->price_per_kg,
            $offer->currency,
        );

        return response()->json($booking, 201);
    }
}
