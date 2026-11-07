<?php

namespace App\Http\Controllers\Vols;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Requests\StoreFlightOrderRequest;
use App\Models\Flights\Airport;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Services\Flights\FlightAudit;
use App\Services\Flights\FlightOfferUnavailable;
use App\Services\Flights\FlightOrderService;
use App\Services\Flights\FlightPriceChanged;
use App\Services\Flights\FlightRefundService;
use App\Services\Flights\FlightSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Billetterie aerienne cote client : recherche -> offre -> passagers ->
 * paiement (checkout commun) -> suivi du dossier.
 */
class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $searches,
        protected FlightOrderService $orders,
        protected FlightRefundService $refunds,
    ) {}

    public function index(Request $request): Response
    {
        $search = null;
        $offers = [];

        if ($request->filled('origin')) {
            $rules = new FlightSearchRequest;
            $input = $request->query();
            $input['origin'] = strtoupper((string) ($input['origin'] ?? ''));
            $input['destination'] = strtoupper((string) ($input['destination'] ?? ''));
            $data = Validator::make($input, $rules->rules(), $rules->messages())->validate();

            $search = $this->searches->search($request->user(), $data);
            $offers = $search->offers()->orderBy('price_total')->get();
        }

        return Inertia::render('Vols/Index', [
            'airports' => Airport::query()->orderBy('city')->get(['iata', 'city', 'name', 'country']),
            'filters' => $request->only(['origin', 'destination', 'depart_date', 'return_date', 'adults', 'children', 'infants', 'cabin']),
            'search' => $search,
            'offers' => $offers,
        ]);
    }

    public function passengers(FlightOffer $offer): Response
    {
        return Inertia::render('Vols/Passengers', [
            'offer' => $offer->load('search'),
            'airports' => Airport::query()->whereIn('iata', [$offer->search->origin, $offer->search->destination])->get(['iata', 'city']),
        ]);
    }

    public function store(StoreFlightOrderRequest $request, FlightOffer $offer): RedirectResponse
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
            return back()
                ->withInput()
                ->with('error', 'Le prix a change : nouveau tarif '.number_format($e->newPrice, 0, ',', ' ').' '.$e->currency.'. Verifiez et confirmez.')
                ->with('new_price', $e->newPrice);
        } catch (FlightOfferUnavailable $e) {
            return redirect()->route('vols.index')->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.show', $order->booking_id)
            ->with('success', 'Prix confirme. Payez pour lancer la reservation : le billet n\'est emis qu\'apres paiement et confirmation.');
    }

    public function mine(Request $request): Response
    {
        return Inertia::render('Vols/Mine', [
            'orders' => FlightOrder::query()
                ->where('user_id', Auth::id())
                ->with('offer.search')
                ->latest()
                ->paginate(10),
            'labels' => $this->labels(),
        ]);
    }

    public function show(FlightOrder $order): Response
    {
        abort_unless($order->user_id === Auth::id(), 403);

        FlightAudit::log('order.viewed', $order, [], 'user:'.Auth::id());

        return Inertia::render('Vols/Show', [
            'order' => $order->load(['offer.search', 'passengers', 'tickets.passenger', 'history', 'refunds', 'segmentStatuses']),
            'statusLabel' => FlightStatus::label($order->status),
            'labels' => $this->labels(),
            'canCancel' => $order->status === FlightStatus::PRICE_CONFIRMED,
            'canRefund' => in_array($order->status, [FlightStatus::TICKETED, FlightStatus::BOOKED], true) && ! $order->refunds()->whereIn('status', ['requested', 'approved'])->exists(),
        ]);
    }

    public function cancel(FlightOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless($order->status === FlightStatus::PRICE_CONFIRMED, 422, 'Ce dossier ne peut plus etre annule en ligne.');

        $this->orders->cancelUnpaid($order, 'user:'.Auth::id());

        return redirect()->route('vols.mine')->with('success', 'Dossier annule.');
    }

    public function refund(Request $request, FlightOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless(in_array($order->status, [FlightStatus::TICKETED, FlightStatus::BOOKED], true), 422);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        $this->refunds->request($order, 'customer_request', $data['reason'], 'user:'.Auth::id());

        return back()->with('success', 'Demande envoyee. Les frais eventuels (compagnie, paiement) vous seront detailles avant confirmation.');
    }

    private function labels(): array
    {
        return collect(FlightStatus::all())->mapWithKeys(fn ($s) => [$s => FlightStatus::label($s)])->all();
    }
}
