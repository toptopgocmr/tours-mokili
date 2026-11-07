<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fret\Claim;
use App\Models\Fret\FreightAuditLog;
use App\Models\Fret\FreightOrder;
use App\Services\Fret\FreightClaimService;
use App\Services\Fret\FreightOrderService;
use App\Services\Fret\FreightTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Back-office : commandes, suivi (retards / evenements manquants), annulations, remboursements, reclamations. */
class FreightOrderController extends Controller
{
    public function __construct(private FreightOrderService $orders, private FreightClaimService $claims, private FreightTrackingService $tracking) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Fret/Orders/Index', [
            'orders' => FreightOrder::query()
                ->with(['user:id,name', 'partner:id,name', 'shipment:id,tracking_code,status'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), fn ($q) => $q->where('reference', 'like', '%'.$request->string('search').'%'))
                // Suivi : commandes confirmees depuis plus de 3 jours sans aucun evenement de transport.
                ->when($request->boolean('stalled'), fn ($q) => $q->where('status', FreightOrder::CONFIRMED)->where('confirmed_at', '<', now()->subDays(3))
                    ->whereDoesntHave('shipment.events'))
                ->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'search', 'stalled']),
            'statuses' => FreightOrder::LABELS,
        ]);
    }

    public function show(FreightOrder $order): Response
    {
        return Inertia::render('Admin/Fret/Orders/Show', [
            'order' => $order->load(['user:id,name,email', 'partner:id,name', 'shipment.events', 'claims', 'booking.payments', 'documents']),
            'statuses' => FreightTrackingService::STATUSES,
            'history' => FreightAuditLog::where('subject_type', 'FreightOrder')->where('subject_id', $order->id)->orderByDesc('id')->get(),
        ]);
    }

    public function cancel(Request $request, FreightOrder $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        try {
            $this->orders->cancel($order, 'admin:'.$request->user()->id, $data['reason']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Commande annulee.');
    }

    public function refund(Request $request, FreightOrder $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->orders->markRefunded($order, $request->user(), $data['reason']);

        return back()->with('success', 'Remboursement enregistre.');
    }

    public function addEvent(Request $request, FreightOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string'], 'location' => ['nullable', 'string', 'max:150'], 'comment' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->tracking->add($order->shipment, $data['status'], $data['location'] ?? null, $data['comment'], FreightTrackingService::actorFor($request->user()));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Evenement ajoute.');
    }

    // --- Reclamations ---

    public function claims(Request $request): Response
    {
        return Inertia::render('Admin/Fret/Claims/Index', [
            'claims' => Claim::with(['order:id,reference,partner_user_id', 'user:id,name'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function claim(Claim $claim): Response
    {
        return Inertia::render('Admin/Fret/Claims/Show', [
            'claim' => $claim->load(['order.partner:id,name', 'user:id,name,email', 'messages.author:id,name']),
        ]);
    }

    public function resolveClaim(Request $request, Claim $claim): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:in_review,documents_requested,resolved,rejected'],
            'resolution' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $this->claims->resolve($request->user(), $claim, $data['status'], $data['resolution']);

        return back()->with('success', 'Reclamation mise a jour.');
    }
}
