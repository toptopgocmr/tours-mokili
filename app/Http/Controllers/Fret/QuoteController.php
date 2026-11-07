<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Http\Requests\FreightQuoteRequestRequest;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\Quote;
use App\Models\Fret\QuoteRequest;
use App\Services\Fret\FreightQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Client : demandes de devis, comparaison des propositions, acceptation explicite. */
class QuoteController extends Controller
{
    public function __construct(private FreightQuoteService $quotes) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Fret/Demandes/Index', [
            'requests' => QuoteRequest::where('user_id', $request->user()->id)->withCount('quotes')->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): Response
    {
        $offer = $request->filled('offre') ? FreightOffer::active()->where('slug', $request->string('offre'))->first() : null;

        return Inertia::render('Fret/Demandes/Create', [
            'offer' => $offer?->only(['id', 'slug', 'title', 'mode', 'origin_city', 'origin_country', 'destination_city', 'destination_country']),
        ]);
    }

    public function store(FreightQuoteRequestRequest $request): RedirectResponse
    {
        $offer = $request->filled('offer_slug') ? FreightOffer::active()->where('slug', $request->string('offer_slug'))->first() : null;

        try {
            $quoteRequest = $this->quotes->createRequest($request->user(), $request->payload(), $offer);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('fret.requests.show', $quoteRequest->reference)->with('success', 'Demande envoyee. Vous serez notifie des propositions.');
    }

    public function show(Request $request, QuoteRequest $quoteRequest): Response
    {
        abort_unless($quoteRequest->user_id === $request->user()->id, 403);

        return Inertia::render('Fret/Demandes/Show', [
            'quoteRequest' => $quoteRequest->load(['quotes.partner:id,name', 'documents', 'offer:id,title,slug']),
        ]);
    }

    public function accept(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'Vous devez accepter explicitement le devis et ses conditions.']);

        try {
            $order = $this->quotes->accept($request->user(), $quote, (bool) $data['accept_terms']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.show', $order->booking_id)->with('success', "Devis accepte. Commande {$order->reference} : procedez au paiement.");
    }

    public function refuse(Request $request, Quote $quote): RedirectResponse
    {
        try {
            $this->quotes->refuse($request->user(), $quote, $request->input('reason'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Devis refuse.');
    }
}
