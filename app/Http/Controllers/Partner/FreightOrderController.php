<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Fret\Claim;
use App\Models\Fret\FreightOrder;
use App\Models\Fret\TrackingEvent;
use App\Services\Fret\FreightClaimService;
use App\Services\Fret\FreightOrderService;
use App\Services\Fret\FreightTrackingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Partenaire : commandes, confirmation de reservation, suivi, reclamations, finances et statistiques. TOUJOURS filtre sur le partenaire connecte. */
class FreightOrderController extends Controller
{
    public function __construct(
        private FreightOrderService $orders,
        private FreightTrackingService $tracking,
        private FreightClaimService $claims,
    ) {}

    private function mine(Request $request)
    {
        return FreightOrder::where('partner_user_id', $request->user()->id);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Fret/Commandes/Index', [
            'orders' => $this->mine($request)->with(['user:id,name', 'shipment:id,tracking_code,status'])->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, FreightOrder $order): Response
    {
        abort_unless($order->partner_user_id === $request->user()->id, 403);

        return Inertia::render('Partner/Fret/Commandes/Show', [
            'order' => $order->load(['user:id,name', 'shipment.events', 'claims.messages', 'documents']),
            'statuses' => FreightTrackingService::STATUSES,
            'canConfirm' => $order->status === FreightOrder::PAID,
        ]);
    }

    public function confirm(Request $request, FreightOrder $order): RedirectResponse
    {
        $data = $request->validate(['instructions' => ['required', 'string', 'min:10', 'max:2000']]);

        try {
            $this->orders->confirmReservation($request->user(), $order, $data['instructions']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reservation confirmee.');
    }

    public function addEvent(Request $request, FreightOrder $order): RedirectResponse
    {
        abort_unless($order->partner_user_id === $request->user()->id, 403);
        $data = $request->validate([
            'status' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:150'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'corrects_event_id' => ['nullable', 'integer'],
        ]);

        try {
            $shipment = $order->shipment;
            $source = FreightTrackingService::actorFor($request->user());
            $at = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null;

            if (! empty($data['corrects_event_id'])) {
                $wrong = TrackingEvent::where('shipment_id', $shipment->id)->findOrFail($data['corrects_event_id']);
                $this->tracking->correct($shipment, $wrong, $data['status'], $data['location'] ?? null, $data['comment'] ?? '', $source);
            } else {
                $this->tracking->add($shipment, $data['status'], $data['location'] ?? null, $data['comment'] ?? null, $source, $at);
            }
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Evenement de suivi enregistre.');
    }

    public function claimMessage(Request $request, Claim $claim): RedirectResponse
    {
        abort_unless($claim->order->partner_user_id === $request->user()->id, 403);
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];
        $this->claims->message($request->user(), $claim, $body);

        return back()->with('success', 'Reponse envoyee.');
    }

    public function claimsIndex(Request $request): Response
    {
        return Inertia::render('Partner/Fret/Reclamations', [
            'claims' => Claim::whereHas('order', fn ($q) => $q->where('partner_user_id', $request->user()->id))->with(['order:id,reference', 'messages.author:id,name'])->latest()->paginate(15),
        ]);
    }

    /** Releve financier et statistiques du partenaire (cadrage section 5 : finances + statistiques). */
    public function finances(Request $request): Response
    {
        $id = $request->user()->id;
        $paid = FreightOrder::where('partner_user_id', $id)->whereNotNull('payment_confirmed_at');

        return Inertia::render('Partner/Fret/Finances', [
            'totals' => [
                'billed' => (float) (clone $paid)->where('status', '!=', FreightOrder::CANCELLED)->sum('total_amount'),
                'commission' => (float) (clone $paid)->where('status', '!=', FreightOrder::CANCELLED)->sum('commission_amount'),
                'partner' => (float) (clone $paid)->where('status', '!=', FreightOrder::CANCELLED)->sum('partner_amount'),
                'settled' => (float) (clone $paid)->whereNotNull('settled_at')->sum('partner_amount'),
                'refunded' => (float) (clone $paid)->whereNotNull('refunded_at')->sum('total_amount'),
            ],
            'stats' => [
                'requests' => DB::table('freight_quote_requests')->where('partner_user_id', $id)->count(),
                'quotes_sent' => DB::table('freight_quotes')->where('partner_user_id', $id)->count(),
                'quotes_accepted' => DB::table('freight_quotes')->where('partner_user_id', $id)->where('status', 'accepted')->count(),
                'orders' => FreightOrder::where('partner_user_id', $id)->count(),
                'delivered' => DB::table('freight_shipments')->where('partner_user_id', $id)->where('status', 'livre')->count(),
            ],
            'orders' => $paid->latest()->limit(50)->get(['id', 'reference', 'status', 'total_amount', 'commission_amount', 'partner_amount', 'currency', 'payment_confirmed_at', 'settled_at', 'refunded_at']),
        ]);
    }
}
