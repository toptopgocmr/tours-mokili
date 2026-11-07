<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FreightQuoteRequestRequest;
use App\Models\Fret\Claim;
use App\Models\Fret\FreightNotification;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\FreightOrder;
use App\Models\Fret\Quote;
use App\Models\Fret\QuoteRequest;
use App\Models\Fret\Shipment;
use App\Services\Fret\FreightClaimService;
use App\Services\Fret\FreightOrderService;
use App\Services\Fret\FreightQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * API Fret & Logistique pour l'application mobile (meme logique metier que le site).
 * Le paiement se fait ensuite via POST /bookings/{booking_id}/pay (Mobile Money).
 */
class FreightMarketController extends Controller
{
    public function __construct(
        private FreightQuoteService $quotes,
        private FreightOrderService $orders,
        private FreightClaimService $claims,
    ) {}

    // --- Devis ---

    public function requests(Request $request): JsonResponse
    {
        return response()->json(QuoteRequest::where('user_id', $request->user()->id)->withCount('quotes')->latest()->paginate(15));
    }

    public function createRequest(FreightQuoteRequestRequest $request): JsonResponse
    {
        $offer = $request->filled('offer_slug') ? FreightOffer::active()->where('slug', $request->string('offer_slug'))->first() : null;

        try {
            $qr = $this->quotes->createRequest($request->user(), $request->payload(), $offer);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($qr, 201);
    }

    public function showRequest(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        abort_unless($quoteRequest->user_id === $request->user()->id, 403);

        return response()->json($quoteRequest->load(['quotes.partner:id,name', 'offer:id,title,slug']));
    }

    public function acceptQuote(Request $request, Quote $quote): JsonResponse
    {
        $data = $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'Vous devez accepter explicitement le devis et ses conditions.']);

        try {
            $order = $this->quotes->accept($request->user(), $quote, (bool) $data['accept_terms']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['order' => $order->load('shipment'), 'booking_id' => $order->booking_id], 201);
    }

    public function refuseQuote(Request $request, Quote $quote): JsonResponse
    {
        try {
            $this->quotes->refuse($request->user(), $quote, $request->input('reason'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'refused']);
    }

    // --- Commandes ---

    public function orders(Request $request): JsonResponse
    {
        return response()->json(FreightOrder::where('user_id', $request->user()->id)->with('shipment:id,tracking_code,status')->latest()->paginate(15));
    }

    public function showOrder(Request $request, FreightOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json($order->load(['shipment.events', 'partner:id,name', 'claims']));
    }

    public function cancelOrder(Request $request, FreightOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_if($order->payment_confirmed_at, 422, 'Commande deja payee : contactez le support ou ouvrez une reclamation.');

        try {
            return response()->json($this->orders->cancel($order, 'user:'.$request->user()->id, $request->input('reason', 'Annulation par le client')));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // --- Suivi public par code (evenements sans donnees personnelles) ---

    public function tracking(string $code): JsonResponse
    {
        $shipment = Shipment::with('events:id,shipment_id,status,location,occurred_at,comment')
            ->where('tracking_code', strtoupper(trim($code)))->first();
        abort_unless($shipment, 404, 'Aucun envoi trouve pour ce code de suivi.');

        return response()->json($shipment->only(['tracking_code', 'origin_city', 'origin_country', 'destination_city', 'destination_country', 'mode', 'status', 'weight_kg', 'picked_up_at', 'delivered_at']) + ['events' => $shipment->events]);
    }

    // --- Reclamations ---

    public function claimsIndex(Request $request): JsonResponse
    {
        return response()->json(Claim::where('user_id', $request->user()->id)->with('order:id,reference')->latest()->paginate(15));
    }

    public function openClaim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_reference' => ['required', 'string', 'exists:freight_orders,reference'],
            'reason' => ['required', 'in:perte,retard,dommage,facturation,autre'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $claim = $this->claims->open($request->user(), FreightOrder::where('reference', $data['order_reference'])->firstOrFail(), $data['reason'], $data['description']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($claim, 201);
    }

    public function claimMessage(Request $request, Claim $claim): JsonResponse
    {
        abort_unless($claim->user_id === $request->user()->id, 403);
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];

        return response()->json($this->claims->message($request->user(), $claim, $body), 201);
    }

    // --- Notifications ---

    public function notifications(Request $request): JsonResponse
    {
        return response()->json(FreightNotification::where('user_id', $request->user()->id)->latest()->limit(50)->get());
    }
}
