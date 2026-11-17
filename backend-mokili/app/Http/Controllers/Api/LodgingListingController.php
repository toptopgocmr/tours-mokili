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
        // Meme recherche que le site : destination (ville, quartier, nom),
        // voyageurs (adultes + enfants) et chambres.
        $guests = $request->filled('adults') || $request->filled('children')
            ? max(1, $request->integer('adults') + $request->integer('children'))
            : $request->integer('guests');

        $listings = LodgingListing::query()
            ->active()
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $w
                ->where('city', 'like', '%'.$request->string('city').'%')
                ->orWhere('title', 'like', '%'.$request->string('city').'%')
                ->orWhere('address', 'like', '%'.$request->string('city').'%')))
            ->when($guests > 0, fn ($q) => $q->where('max_guests', '>=', $guests))
            ->when($request->integer('rooms') > 1, fn ($q) => $q->where('bedrooms', '>=', $request->integer('rooms')))
            // Villa, hotel, appartement.
            ->when($request->string('property_type')->isNotEmpty(), fn ($q) => $q->where('property_type', $request->string('property_type')))
            ->tap(fn ($q) => match ($request->input('sort')) {
                'prix_asc' => $q->orderBy('price_per_night'),
                'prix_desc' => $q->orderByDesc('price_per_night'),
                'capacite' => $q->orderByDesc('max_guests')->orderByDesc('bedrooms'),
                default => $q->orderByDesc('discount_percent')->latest(),
            })
            ->paginate(10)
            ->withQueryString();

        // Dates choisies : signale les hebergements deja reserves sur ces dates.
        $busy = \App\Http\Controllers\Logement\LodgingListingController::busyIds(
            $listings->getCollection()->pluck('id')->all(), $request->input('check_in'), $request->input('check_out')
        );
        $listings->getCollection()->transform(fn (LodgingListing $l) => $l->setAttribute('booked_on_dates', in_array($l->id, $busy, true)));

        return response()->json($listings);
    }

    /**
     * Accueil Hébergement de l'app (comme la page web) : compteurs Villa /
     * Hotel / Appartement et "Destinations en vogue".
     */
    public function home(): JsonResponse
    {
        $pool = LodgingListing::query()->active()->get(['id', 'property_type']);

        return response()->json([
            'types' => collect(config('catalogue.logement.property_types'))->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'count' => $pool->where('property_type', $key)->count(),
            ])->values(),
            'sorts' => \App\Http\Controllers\Logement\LodgingListingController::SORTS,
            'popular_cities' => \App\Http\Controllers\Logement\LodgingListingController::popularCities(),
        ]);
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
            (float) $listing->final_price_per_night,
            $listing->currency,
        );

        return response()->json($booking, 201);
    }
}
