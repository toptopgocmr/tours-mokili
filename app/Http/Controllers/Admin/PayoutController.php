<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Decaissements Mobile Money (PAY-OUT) depuis le back-office : rembourser
 * un client, reverser a un partenaire, payer un fournisseur... via Peex,
 * pawaPay, MaishaPay ou Digitwace. Reserve aux admins (pas aux agents).
 */
class PayoutController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentGatewayManager $manager,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => Payout::query()
                ->with(['creator:id,name', 'booking:id,reference'])
                ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->string('gateway')->isNotEmpty(), fn ($q) => $q->where('gateway', $request->string('gateway')))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('status', 'gateway'),
            'methods' => $this->manager->catalogue('payout'),
            'stats' => [
                'totalPaid' => Payout::where('status', 'paid')->sum('amount'),
                'pendingCount' => Payout::whereIn('status', ['pending', 'processing'])->count(),
                'failedCount' => Payout::where('status', 'failed')->count(),
            ],
            // Pre-remplissage "Rembourser" depuis la page Paiements.
            'prefill' => $request->filled('payment')
                ? optional(Payment::with('user', 'booking')->find($request->integer('payment')), fn (Payment $p) => [
                    'payment_id' => $p->id,
                    'booking_id' => $p->booking_id,
                    'user_id' => $p->user_id,
                    'beneficiary_name' => $p->user?->name,
                    'phone' => $p->phone,
                    'country_code' => $p->country_code ?? 'CG',
                    'network' => $p->network,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'gateway' => $p->provider,
                    'reason' => 'Remboursement reservation '.($p->booking?->reference ?? ''),
                ])
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403, 'Seul un administrateur peut decaisser des fonds.');

        $data = $request->validate([
            'gateway' => ['required', Rule::in(array_keys(PaymentGatewayManager::GATEWAYS))],
            'beneficiary_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:6', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'network' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['nullable', 'string', 'max:255'],
            'payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $payout = $this->payments->payout($data, $request->user());
        } catch (PaymentGatewayException $e) {
            return back()->with('error', 'Decaissement impossible : '.$e->getMessage());
        }

        // Remboursement d'un paiement : on trace le lien cote paiement.
        if ($payout->payment_id && $payout->status !== 'failed') {
            Payment::whereKey($payout->payment_id)->where('status', 'paid')->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $payout->reason ?? 'Remboursement Mobile Money',
            ]);
            $payout->booking?->update(['status' => 'refunded']);
        }

        return redirect()->route('admin.payouts.index')->with(
            $payout->status === 'failed' ? 'error' : 'success',
            match ($payout->status) {
                'paid' => 'Decaissement effectue.',
                'failed' => 'Decaissement refuse : '.$payout->failure_reason,
                default => 'Decaissement envoye a '.$payout->gateway.', en attente de confirmation.',
            }
        );
    }

    public function refresh(Payout $payout): RedirectResponse
    {
        $payout = $this->payments->refreshPayout($payout);

        return back()->with('success', 'Statut actuel : '.$payout->status.'.');
    }
}
