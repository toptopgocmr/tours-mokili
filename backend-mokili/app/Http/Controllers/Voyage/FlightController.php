<?php

namespace App\Http\Controllers\Voyage;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Requests\StoreFlightBookingRequest;
use App\Models\Flights\Airport;
use App\Models\Flights\FlightAfterSale;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Services\Flights\FlightBookingService;
use App\Services\Flights\FlightException;
use App\Services\Flights\FlightNotifier;
use App\Services\Flights\FlightSearchService;
use App\Services\Flights\FlightStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOYAGE > VOLS : billetterie aerienne cote client (site web).
 * recherche -> resultats -> passagers -> checkout -> suivi du dossier.
 */
class FlightController extends Controller
{
    public const PAYMENT_COLUMNS = ['id', 'reference', 'booking_id', 'provider', 'status', 'amount', 'currency', 'paid_at', 'refunded_at', 'created_at'];

    public function __construct(
        protected FlightSearchService $searchService,
        protected FlightBookingService $bookingService,
    ) {}

    public function airports(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $airports = Airport::query()
            ->active()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('iata_code', 'like', $q.'%')
                ->orWhere('city', 'like', '%'.$q.'%')
                ->orWhere('name', 'like', '%'.$q.'%')
                ->orWhere('country_name', 'like', $q.'%')))
            ->orderByDesc('popularity')
            ->limit(12)
            ->get(['iata_code', 'name', 'city', 'country_code', 'country_name']);

        return response()->json($airports);
    }

    /** Programme des vols (departs / arrivees en direct, AirLabs). */
    public function schedules(Request $request, \App\Services\Flights\Schedules\AirLabsSchedules $airlabs): Response
    {
        $data = $this->scheduleData($request, $airlabs);
        $data['airportList'] = $this->airportList();

        return Inertia::render('Voyage/Vols/Horaires', $data);
    }

    /** Aeroports (ville + code IATA) pour les listes deroulantes du programme des vols. */
    public function airportList()
    {
        return Airport::query()->active()
            ->orderBy('country_name')->orderByDesc('popularity')->orderBy('city')
            ->get(['iata_code', 'city', 'name', 'country_name']);
    }

    public function scheduleData(Request $request, \App\Services\Flights\Schedules\AirLabsSchedules $airlabs): array
    {
        $airport = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $request->query('airport', 'BZV'))) ?: 'BZV';
        $airport = strlen($airport) === 3 ? $airport : 'BZV';
        $other = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $request->query('to', '')));
        $other = strlen($other) === 3 ? $other : null;
        $direction = $request->query('direction') === 'arrivals' ? 'arrivals' : 'departures';

        $board = $airlabs->board($airport, $direction, $other);
        $codes = collect($board['flights'])->flatMap(fn ($f) => [$f['origin'], $f['destination']])->push($airport, $other)->filter()->unique();

        return [
            'airport' => $airport,
            'to' => $other,
            'direction' => $direction,
            'flights' => $board['flights'],
            'error' => $board['error'],
            'airports' => Airport::whereIn('iata_code', $codes)->get(['iata_code', 'name', 'city', 'country_name'])->keyBy('iata_code'),
        ];
    }

    public function search(FlightSearchRequest $request): RedirectResponse
    {
        $search = $this->searchService->search($request->validated(), $request->user());

        // "Vols directs uniquement" : les resultats s'ouvrent filtres sur 0 escale.
        return redirect()->route('voyage.vols.results', array_filter([
            'uuid' => $search->uuid,
            'direct' => $request->boolean('direct_only') ? '1' : null,
        ], fn ($v) => $v !== null));
    }

    public function results(Request $request, string $uuid): Response
    {
        $search = FlightSearch::where('uuid', $uuid)->firstOrFail();
        $filters = $request->only('stops', 'airline', 'sort', 'direct');
        $offers = $this->searchService->offersFor($search, $filters);

        return Inertia::render('Voyage/Vols/Results', [
            'search' => $search,
            'airports' => $this->airportsFor($search, $offers),
            'offers' => $offers->values(),
            'airlines' => $search->offers()->get()->unique('validating_airline')
                ->map(fn ($o) => ['code' => $o->validating_airline, 'name' => $o->airline_name])->values(),
            'filters' => (object) $filters,
            'expired' => $search->created_at->lt(now()->subMinutes((int) config('flights.offer_ttl_minutes', 30))),
            'supplierErrors' => $search->supplier_errors,
            'totalCount' => (int) $search->results_count,
            'cabins' => config('flights.cabins'),
        ]);
    }

    public function offer(FlightOffer $flightOffer): Response|RedirectResponse
    {
        $search = $flightOffer->search;
        if ($flightOffer->is_expired && $search->created_at->lt(now()->subHours(6))) {
            return redirect()->route('voyage.vols.results', $search->uuid)
                ->with('error', 'Cette offre a expire. Relancez la recherche pour obtenir les prix a jour.');
        }

        $user = Auth::user();

        return Inertia::render('Voyage/Vols/Book', [
            'offer' => $flightOffer,
            'search' => $search,
            'airports' => $this->airportsFor($search, [$flightOffer]),
            'international' => Airport::whereIn('iata_code', $search->airportCodes())->distinct()->count('country_code') > 1,
            'contact' => ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone],
            'cabins' => config('flights.cabins'),
        ]);
    }

    public function book(StoreFlightBookingRequest $request, FlightOffer $flightOffer): RedirectResponse
    {
        try {
            $flight = $this->bookingService->createFromOffer($flightOffer, $request->user(), $request->validated());
        } catch (FlightException $e) {
            if ($e->reason === 'unavailable') {
                return redirect()->route('voyage.vols.results', $flightOffer->search->uuid)->with('error', $e->getMessage());
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('checkout.show', $flight->booking_id)
            ->with('success', "Prix confirme. Dossier {$flight->reference} cree : choisissez votre moyen de paiement.");
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Voyage/Vols/Dossiers', [
            'dossiers' => FlightBooking::query()
                ->visibleTo($request->user())
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Request $request, FlightBooking $flightBooking): Response
    {
        $this->authorizeView($request, $flightBooking);

        return Inertia::render('Voyage/Vols/Dossier', [
            'dossier' => $flightBooking->load(['passengers.ticket', 'afterSales', 'booking.payments' => fn ($q) => $q->latest()->select(self::PAYMENT_COLUMNS)]),
            'history' => $flightBooking->histories()->get(['to_status', 'note', 'created_at']),
            'message' => FlightNotifier::message($flightBooking),
            'afterSaleTypes' => FlightAfterSale::TYPES,
            'canPay' => in_array($flightBooking->status, [FlightStatus::PRICE_CONFIRMED, FlightStatus::PAYMENT_PENDING], true),
            'canConfirmPrice' => $flightBooking->status === FlightStatus::SEARCHED,
            'canCancel' => ! $flightBooking->isPaid() && in_array($flightBooking->status, [FlightStatus::SEARCHED, FlightStatus::PRICE_CONFIRMED, FlightStatus::PAYMENT_PENDING], true),
            'canRequestAfterSale' => $flightBooking->isPaid() && ! in_array($flightBooking->status, [FlightStatus::REFUNDED, FlightStatus::REFUND_PENDING], true),
        ]);
    }

    public function confirmPrice(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $this->authorizeView($request, $flightBooking);

        try {
            $flight = $this->bookingService->confirmNewPrice($flightBooking);
        } catch (FlightException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.show', $flight->booking_id)->with('success', 'Nouveau prix confirme.');
    }

    public function cancel(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $this->authorizeView($request, $flightBooking);

        try {
            $this->bookingService->cancelUnpaid($flightBooking);
        } catch (FlightException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dossier annule.');
    }

    /** Demande apres-vente en ligne, sans deplacement (section 10). */
    public function afterSale(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $this->authorizeView($request, $flightBooking);
        abort_unless($flightBooking->isPaid(), 422, 'Dossier non paye.');

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(FlightAfterSale::TYPES))],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ]);

        if ($flightBooking->afterSales()->whereIn('status', ['submitted', 'in_review'])->exists()) {
            return back()->with('error', 'Une demande est deja en cours de traitement pour ce dossier.');
        }

        $files = collect($request->file('attachments', []))
            ->map(fn ($f) => $f->store("flights/{$flightBooking->reference}", 'local'))
            ->all();

        $flightBooking->afterSales()->create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'reason' => $data['reason'],
            'attachments' => $files ?: null,
            'status' => 'submitted',
            'requested_amount' => $data['type'] === 'change' ? null : $flightBooking->total_amount,
        ]);
        $flightBooking->update(['after_sales_status' => 'submitted', 'needs_attention' => true, 'attention_reason' => 'Nouvelle demande apres-vente']);
        app(FlightNotifier::class)->alertOps($flightBooking->fresh(), 'Nouvelle demande apres-vente : '.FlightAfterSale::TYPES[$data['type']]);

        return back()->with('success', 'Demande envoyee. Notre equipe billetterie vous repond rapidement.');
    }

    /** Recu de paiement, confirmation de reservation, billet electronique. */
    public function document(Request $request, FlightBooking $flightBooking, string $type): Response
    {
        $this->authorizeView($request, $flightBooking);

        $allowed = match ($type) {
            'recu' => $flightBooking->paid_at !== null,
            'confirmation' => $flightBooking->booked_at !== null,
            'billet' => $flightBooking->status === FlightStatus::TICKETED || ($flightBooking->ticketed_at !== null && $flightBooking->status !== FlightStatus::REFUNDED),
            default => false,
        };
        abort_unless($allowed, 404);

        return Inertia::render('Voyage/Vols/Document', [
            'type' => $type,
            'dossier' => $flightBooking->load(['passengers.ticket', 'booking.payments' => fn ($q) => $q->where('status', 'paid')->select(self::PAYMENT_COLUMNS)]),
        ]);
    }

    protected function authorizeView(Request $request, FlightBooking $flight): void
    {
        $visible = FlightBooking::query()->visibleTo($request->user())->whereKey($flight->id)->exists();
        abort_unless($visible, 403);
    }

    /** Aeroports a afficher : voyage + escales et correspondances des offres. */
    protected function airportsFor(FlightSearch $search, iterable $offers)
    {
        $codes = $search->airportCodes();
        foreach ($offers as $offer) {
            foreach ($offer->slices ?? [] as $slice) {
                $codes[] = $slice['via'] ?? null;
                foreach ($slice['segments'] ?? [] as $seg) {
                    $codes[] = $seg['origin'] ?? null;
                    $codes[] = $seg['destination'] ?? null;
                }
            }
        }

        return Airport::whereIn('iata_code', array_unique(array_filter($codes)))->get()->keyBy('iata_code');
    }
}
