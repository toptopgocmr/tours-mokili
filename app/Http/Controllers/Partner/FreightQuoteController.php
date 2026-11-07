<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\Fret\QuoteRequest;
use App\Services\Fret\FreightQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Partenaire : demandes de devis recues (adressees ou ouvertes sur ses modes) et reponses detaillees. */
class FreightQuoteController extends Controller
{
    public function __construct(private FreightQuoteService $quotes) {}

    /** Isolation stricte : demandes adressees a ce partenaire, ou ouvertes (sans partenaire) sur ses modes. */
    private function visible(Request $request)
    {
        $profile = FreightPartnerProfile::where('user_id', $request->user()->id)->first();
        $modes = $profile?->modes ?? [];
        $id = $request->user()->id;

        return QuoteRequest::query()->where(function ($q) use ($id, $modes) {
            $q->where('partner_user_id', $id)
                ->orWhere(fn ($o) => $o->whereNull('partner_user_id')->whereIn('mode', $modes ?: ['__none__']));
        });
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Fret/Demandes/Index', [
            'requests' => $this->visible($request)->with('user:id,name')->withCount('quotes')->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, QuoteRequest $quoteRequest): Response
    {
        $this->authorizeView($request, $quoteRequest);

        return Inertia::render('Partner/Fret/Demandes/Show', [
            'quoteRequest' => $quoteRequest->load(['user:id,name', 'documents', 'quotes' => fn ($q) => $q->where('partner_user_id', $request->user()->id)]),
            'defaults' => ['validity_days' => config('freight.quote_validity_days'), 'valid_until' => now()->addDays(config('freight.quote_validity_days'))->toDateString()],
        ]);
    }

    public function respond(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $this->authorizeView($request, $quoteRequest);
        $profile = FreightPartnerProfile::where('user_id', $request->user()->id)->first();
        abort_unless($profile?->isApproved(), 403, 'Votre compte partenaire n\'est pas approuve.');

        $data = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.label' => ['required', 'string', 'max:200'],
            'lines.*.amount' => ['required', 'numeric', 'min:-100000000', 'max:100000000'],
            'lines.*.kind' => ['required', 'in:included,excluded,estimated,third_party'],
            'is_firm' => ['boolean'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'transit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'conditions' => ['nullable', 'string', 'max:3000'],
            'exclusions' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->quotes->respond($request->user(), $quoteRequest, $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('partner.fret.requests.index')->with('success', 'Devis transmis au client.');
    }

    private function authorizeView(Request $request, QuoteRequest $quoteRequest): void
    {
        abort_unless($this->visible($request)->whereKey($quoteRequest->id)->exists(), 403);
    }
}
