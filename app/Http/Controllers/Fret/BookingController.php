<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use App\Services\Fret\FreightOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Tarif instantane : reservation directe depuis la grille du partenaire.
 * Le prix est RECALCULE cote serveur ; la commande fige les conditions
 * (grille, validite, exclusions) et cree le colis + son code de suivi.
 * Le paiement reste distinct de la reservation (confirmee par le partenaire).
 */
class BookingController extends Controller
{
    public function store(Request $request, FreightOffer $freightOffer, FreightOrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:10000000'],
            'volume_m3' => ['nullable', 'numeric', 'min:0.001', 'max:100000'],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $order = $orders->createInstant($request->user(), $freightOffer, [
                'weight_kg' => (float) $data['weight_kg'],
                'volume_m3' => isset($data['volume_m3']) ? (float) $data['volume_m3'] : null,
                'dimensions' => $data['dimensions'] ?? null,
            ], $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.show', $order->booking_id)
            ->with('success', "Commande {$order->reference} creee. Code de suivi : {$order->shipment->tracking_code}.");
    }
}
