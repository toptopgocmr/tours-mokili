<?php

namespace App\Http\Controllers\Logement;

use App\Http\Controllers\Controller;
use App\Models\Logement\LodgingListing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// LOGEMENT module - search/filter UX modeled on Booking.com: destination
// + dates + guests up top, price range in a filter sidebar. Check-in/
// check-out dates aren't matched against a real availability calendar
// yet (no per-night inventory table), so they're carried through as
// search context into the reservation form on Show rather than used to
// exclude already-booked listings.
class LodgingListingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['city', 'check_in', 'check_out', 'guests', 'price_min', 'price_max']);

        return Inertia::render('Logement/Index', [
            'listings' => LodgingListing::query()
                ->active()
                ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                ->when($request->filled('guests'), fn ($q) => $q->where('max_guests', '>=', $request->integer('guests')))
                ->when($request->filled('price_min'), fn ($q) => $q->where('price_per_night', '>=', $request->float('price_min')))
                ->when($request->filled('price_max'), fn ($q) => $q->where('price_per_night', '<=', $request->float('price_max')))
                ->latest()
                ->paginate(9)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(LodgingListing $lodgingListing): Response
    {
        abort_unless($lodgingListing->is_active && $lodgingListing->status === 'published', 404);

        return Inertia::render('Logement/Show', [
            'listing' => $lodgingListing,
            'search' => request()->only(['check_in', 'check_out', 'guests']),
        ]);
    }
}
