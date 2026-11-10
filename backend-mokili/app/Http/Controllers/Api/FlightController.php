<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Voyage\FlightController as WebFlightController;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Requests\StoreFlightBookingRequest;
use App\Models\Flights\FlightAfterSale;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Services\Flights\FlightBookingService;
use App\Services\Flights\FlightException;
use App\Services\Flights\FlightNotifier;
use App\Services\Flights\FlightSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API billetterie aerienne pour l'application mobile (Sanctum). Meme moteur
 * que le site : le statut renvoye est toujours le statut reel du dossier.
 */
class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $searchService,
        protected FlightBookingService $bookingService,
    ) {}

    public function airports(Request $request): JsonResponse
    {
        return app(WebFlightController::class)->airports($request);
    }

    /** Programme des vols : ?airport=BZV&direction=departures|arrivals&to=CDG */
    public function schedules(Request $request, \App\Services\Flights\Schedules\AirLabsSchedules $airlabs): JsonResponse
    {
        $web = app(WebFlightController::class);

        return response()->json($web->scheduleData($request, $airlabs) + ['airport_list' => $web->airportList()]);
    }

    public function search(FlightSearchRequest $request): JsonResponse
    {
        $search = $this->searchService->search($request->validated(), $request->user('sanctum'));
        $filters = $request->only('stops', 'airline', 'sort') + ($request->boolean('direct_only') ? ['stops' => '0'] : []);

        return response()->json([
            'search' => $search,
            'offers' => $this->searchService->offersFor($search, $filters),
        ], 201);
    }

    public function offers(Request $request, string $uuid): JsonResponse
    {
        $search = FlightSearch::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'search' => $search,
            'offers' => $this->searchService->offersFor($search, $request->only('stops', 'airline', 'sort')),
        ]);
    }

    public function offer(FlightOffer $flightOffer): JsonResponse
    {
        return response()->json(['offer' => $flightOffer, 'search' => $flightOffer->search]);
    }

    public function book(StoreFlightBookingRequest $request, FlightOffer $flightOffer): JsonResponse
    {
        try {
            $flight = $this->bookingService->createFromOffer($flightOffer, $request->user(), $request->validated());
        } catch (FlightException $e) {
            return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason] + $e->data, 409);
        }

        // booking_id : a payer via POST /api/bookings/{id}/pay (checkout commun).
        return response()->json(['dossier' => $flight, 'booking_id' => $flight->booking_id], 201);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(FlightBooking::query()->visibleTo($request->user())
            ->where('user_id', $request->user()->id)->latest()->paginate(20));
    }

    public function show(Request $request, FlightBooking $flightBooking): JsonResponse
    {
        $this->authorizeView($request, $flightBooking);

        return response()->json([
            'dossier' => $flightBooking->load(['passengers.ticket', 'afterSales']),
            'message' => FlightNotifier::message($flightBooking),
        ]);
    }

    public function confirmPrice(Request $request, FlightBooking $flightBooking): JsonResponse
    {
        $this->authorizeView($request, $flightBooking);
        try {
            $flight = $this->bookingService->confirmNewPrice($flightBooking);
        } catch (FlightException $e) {
            return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason], 409);
        }

        return response()->json(['dossier' => $flight, 'booking_id' => $flight->booking_id]);
    }

    public function afterSale(Request $request, FlightBooking $flightBooking): JsonResponse
    {
        $this->authorizeView($request, $flightBooking);
        abort_unless($flightBooking->isPaid(), 422, 'Dossier non paye.');

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(FlightAfterSale::TYPES))],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($flightBooking->afterSales()->whereIn('status', ['submitted', 'in_review'])->exists()) {
            return response()->json(['message' => 'Une demande est deja en cours.'], 409);
        }

        $request_ = $flightBooking->afterSales()->create($data + ['user_id' => $request->user()->id, 'status' => 'submitted']);
        $flightBooking->update(['after_sales_status' => 'submitted', 'needs_attention' => true, 'attention_reason' => 'Nouvelle demande apres-vente']);
        app(FlightNotifier::class)->alertOps($flightBooking->fresh(), 'Nouvelle demande apres-vente (app)');

        return response()->json($request_, 201);
    }

    protected function authorizeView(Request $request, FlightBooking $flight): void
    {
        abort_unless(FlightBooking::query()->visibleTo($request->user())->whereKey($flight->id)->exists(), 403);
    }
}
