<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flights\FlightAuditLog;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightRefund;
use App\Models\Flights\FlightStatus;
use App\Services\Flights\FlightFulfillmentService;
use App\Services\Flights\InvalidFlightTransition;
use App\Services\Flights\FlightRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Back-office vols : suivi des dossiers, anomalies, relance, remboursements. */
class FlightOrderController extends Controller
{
    public function __construct(
        protected FlightFulfillmentService $fulfillment,
        protected FlightRefundService $refunds,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Vols/Index', [
            'orders' => FlightOrder::query()
                ->with('user:id,name,email')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->boolean('anomaly'), fn ($q) => $q->where('is_anomaly', true))
                ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('reference', 'like', '%'.$request->string('search').'%')
                    ->orWhere('pnr', 'like', '%'.$request->string('search').'%')
                    ->orWhere('contact_email', 'like', '%'.$request->string('search').'%')))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'anomaly', 'search']),
            'statuses' => collect(FlightStatus::all())->map(fn ($s) => ['value' => $s, 'label' => FlightStatus::label($s)])->values(),
            'anomalies' => FlightOrder::where('is_anomaly', true)->count(),
        ]);
    }

    public function show(FlightOrder $order): Response
    {
        return Inertia::render('Admin/Vols/Show', [
            'order' => $order->load(['user:id,name,email', 'offer.search', 'supplier', 'passengers', 'tickets.passenger', 'history', 'operations', 'refunds', 'commission', 'segmentStatuses']),
            'statusLabel' => FlightStatus::label($order->status),
            'audit' => FlightAuditLog::where('flight_order_id', $order->id)->orderByDesc('id')->limit(100)->get(),
            'canRetry' => $order->status === FlightStatus::FAILED,
        ]);
    }

    public function retry(Request $request, FlightOrder $order): RedirectResponse
    {
        try {
            $this->fulfillment->retry($order, 'admin:'.$request->user()->id);
        } catch (InvalidFlightTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Relance effectuee (le fournisseur est interroge avant toute nouvelle emission).');
    }

    public function refund(Request $request, FlightOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'supplier_fee' => ['nullable', 'numeric', 'min:0'],
            'psp_fee' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->refunds->request(
            $order, 'admin_manual', $data['reason'], 'admin:'.$request->user()->id,
            isset($data['amount']) ? (float) $data['amount'] : null, null,
            (float) ($data['supplier_fee'] ?? 0), (float) ($data['psp_fee'] ?? 0), (float) ($data['penalty'] ?? 0),
        );

        return back()->with('success', 'Demande de remboursement creee.');
    }

    public function markRefunded(Request $request, FlightRefund $refund): RedirectResponse
    {
        abort_unless(in_array($refund->status, ['requested', 'approved'], true), 422);
        $this->refunds->markRefunded($refund, $request->user()->id);

        return back()->with('success', 'Remboursement marque comme effectue.');
    }

    public function rejectRefund(Request $request, FlightRefund $refund): RedirectResponse
    {
        abort_unless(in_array($refund->status, ['requested', 'approved'], true), 422);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->refunds->reject($refund, $request->user()->id, $data['reason']);

        return back()->with('success', 'Remboursement rejete.');
    }
}
