<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOrder;
use App\Services\Fret\FreightClaimService;
use App\Services\Fret\FreightOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Client : mes commandes de fret, suivi, annulation, reclamation. */
class OrderController extends Controller
{
    public function __construct(private FreightOrderService $orders, private FreightClaimService $claims) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Fret/Commandes/Index', [
            'orders' => FreightOrder::where('user_id', $request->user()->id)->with('shipment:id,tracking_code,status')->latest()->paginate(10),
        ]);
    }

    public function show(Request $request, FreightOrder $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return Inertia::render('Fret/Commandes/Show', [
            'order' => $order->load(['shipment.events', 'partner:id,name', 'claims', 'documents']),
            'canCancel' => $order->canTransition(FreightOrder::CANCELLED) && ! $order->payment_confirmed_at,
            'canClaim' => (bool) $order->payment_confirmed_at && $order->status !== FreightOrder::CANCELLED,
        ]);
    }

    public function cancel(Request $request, FreightOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? 'Annulation par le client';

        try {
            // En ligne : uniquement avant paiement ; apres paiement, passer par une reclamation / le support.
            abort_if($order->payment_confirmed_at, 422, 'Commande deja payee : contactez le support ou ouvrez une reclamation.');
            $this->orders->cancel($order, 'user:'.$request->user()->id, $reason);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('fret.orders.index')->with('success', 'Commande annulee.');
    }

    public function claim(Request $request, FreightOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'in:perte,retard,dommage,facturation,autre'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $this->claims->open($request->user(), $order, $data['reason'], $data['description']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reclamation ouverte.');
    }
}
