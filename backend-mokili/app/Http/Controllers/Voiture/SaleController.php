<?php

namespace App\Http\Controllers\Voiture;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOITURE - vente facon Spoticar : reservation du vehicule par un acompte
 * paye en Mobile Money (deduit du prix chez le vendeur) et comparateur.
 */
class SaleController extends Controller
{
    public const HOLD_MINUTES = 30;

    /** Vehicule deja reserve par un acompte paye (ou en cours de paiement). */
    public static function isReserved(Vehicle $vehicle, ?int $exceptUser = null): bool
    {
        return Booking::query()->where('bookable_type', Vehicle::class)->where('bookable_id', $vehicle->id)
            ->where('meta->kind', 'acompte_vente')
            ->when($exceptUser, fn ($q) => $q->where('user_id', '!=', $exceptUser))
            ->where(fn ($q) => $q->whereIn('status', ['confirmed', 'paid', 'completed'])
                ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '>=', now()->subMinutes(self::HOLD_MINUTES))))
            ->exists();
    }

    public static function deposit(Vehicle $vehicle): int
    {
        $d = (int) config('catalogue.voiture.sale_deposit', 100000);

        return (int) min($d, max(0, (float) ($vehicle->display_price['amount'] ?? $d)));
    }

    public function reserve(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published' && $vehicle->offer_type === 'vente', 404);
        if ($vehicle->is_demo) {
            throw ValidationException::withMessages(['reserve' => "Annonce d'exemple : ce vehicule ne peut pas etre reserve."]);
        }
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = DB::transaction(function () use ($vehicle, $request, $data) {
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->first();
            if (self::isReserved($vehicle)) {
                throw ValidationException::withMessages(['reserve' => 'Ce vehicule vient d\'etre reserve par un autre client.']);
            }
            $amount = self::deposit($vehicle);

            return Booking::create([
                'user_id' => $request->user()->id,
                'bookable_type' => Vehicle::class,
                'bookable_id' => $vehicle->id,
                'quantity' => 1,
                'unit_price' => $amount,
                'total_amount' => $amount,
                'currency' => $vehicle->currency,
                'status' => 'awaiting_payment',
                'meta' => [
                    'kind' => 'acompte_vente',
                    'city' => $vehicle->city,
                    'sale_price' => $vehicle->display_price['amount'] ?? null,
                    'contact_phone' => $data['phone'] ?? $request->user()->phone,
                ],
                'notes' => $data['message'] ?? null,
            ]);
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Vehicule bloque pour vous pendant '.self::HOLD_MINUTES.' minutes : payez l\'acompte pour le reserver.');
    }

    /** Comparateur (2 a 4 vehicules). */
    public function compare(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($i) => (int) $i)->filter()->unique()->take(4);

        return Inertia::render('Voiture/Compare', [
            'vehicles' => Vehicle::query()->active()->whereIn('id', $ids)->get()
                ->sortBy(fn ($v) => $ids->search($v->id))->values(),
        ]);
    }
}
