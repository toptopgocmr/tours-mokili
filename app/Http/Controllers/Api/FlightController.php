<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Requests\StoreFlightOrderRequest;
use App\Models\Flights\Airport;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Services\Flights\FlightOfferUnavailable;
use App\Services\Flights\FlightOrderService;
use App\Services\Flights\FlightPriceChanged;
use App\Services\Flights\FlightRefundService;
use App\Services\Flights\FlightSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API vols (site, espace partenaire et application mobile).
 * Le paiement se fait ensuite via POST /bookings/{booking_id}/pay.
 */
class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $searches,
        protected FlightOrderService $orders,
        protected FlightRefundService $refunds,
    ) {}

    public function airports(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        return response()->json(Airport::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('iata', 'like', strtoupper($q).'%')
                ->orWhere('city', 'like', $q.'%')
                ->orWhere('name', 'like', '%'.$q.'%')))
            ->orderBy('city')
            ->limit(20)
            ->get(['iata', 'city', 'name', 'country']));
    }

    public function search(FlightSearchRequest $request): JsonResponse
    {
        $search = $this->searches->search($request->user('sanctum'), $request->validated());

        return response()->json([
            'search' => $search->only(['id', 'origin', 'destination', 'depart_date', 'return_date', 'adults', 'children', 'infants', 'cabin', 'results_count', 'supplier_errors']),
            'offers' => $search->offers()->orderBy('price_total')->get(),
        ]);
    }

    public function order(StoreFlightOrderRequest $request, FlightOffer $offer): JsonResponse
    {
        try {
            $order = $this->orders->createFromOffer(
                $request->user(),
                $offer->load(['search', 'supplier']),
                $request->validated('passengers'),
                ['email' => $request->validated('contact_email'), 'phone' => $request->validated('contact_phone')],
                $request->filled('accepted_price') ? (float) $request->input('accepted_price') : null,
            );
        } catch (FlightPriceChanged $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'price_changed', 'new_price' => $e->newPrice, 'currency' => $e->currency], 409);
        } catch (FlightOfferUnavailable $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'offer_unavailable'], 410);
        }

        return response()->json([
            'order' => $order->load('passengers'),
            'status_label' => FlightStatus::label($order->status),
            'booking_id' => $order->booking_id,
            'pay_url' => "/api/bookings/{$order->booking_id}/pay",
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(FlightOrder::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15)
            ->through(fn (FlightOrder $o) => $this->present($o)));
    }

    public function show(Request $request, FlightOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json($this->present($order->load(['offer.search', 'passengers', 'tickets.passenger', 'refunds', 'segmentStatuses'])));
    }

    public function cancel(Request $request, FlightOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->status === FlightStatus::PRICE_CONFIRMED, 422, 'Ce dossier ne peut plus etre annule en ligne.');

        $order = $this->orders->cancelUnpaid($order, 'user:'.$request->user()->id);

        return response()->json($this->present($order));
    }

    public function refund(Request $request, FlightOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless(in_array($order->status, [FlightStatus::TICKETED, FlightStatus::BOOKED], true), 422, 'Remboursement impossible a ce stade.');

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->refunds->request($order, 'customer_request', $data['reason'], 'user:'.$request->user()->id);

        return response()->json($this->present($order->fresh(['refunds'])));
    }

    /** Statut REEL du dossier : l'application ne doit jamais annoncer un billet avant TICKETED. */
    private function present(FlightOrder $order): array
    {
        return $order->toArray() + [
            'status_label' => FlightStatus::label($order->status),
            'ticket_issued' => $order->status === FlightStatus::TICKETED,
        ];
    }
}
