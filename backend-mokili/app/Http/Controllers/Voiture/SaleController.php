<?php

namespace App\Http\Controllers\Voiture;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Guide\VisitRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VOITURE - vente : prise de rendez-vous (voir, essayer, acheter) avec
 * paiement sur place chez le vendeur - plus de paiement en ligne - et
 * comparateur. isReserved() garde les anciennes reservations par acompte.
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

    /** Ancienne reservation par acompte en ligne : remplacee par le rendez-vous. */
    public function reserve(Request $request, Vehicle $vehicle): RedirectResponse
    {
        throw ValidationException::withMessages(['reserve' => "La vente en ligne n'est plus proposee : prenez rendez-vous, le paiement se fait sur place."]);
    }

    /** Rendez-vous pour voir, essayer ou acheter le vehicule (paiement sur place). */
    public function appointment(Request $request, Vehicle $vehicle): RedirectResponse
    {
        self::createAppointment($request, $vehicle);

        return back()->with('success', 'Demande de rendez-vous envoyee. Le vendeur / l\'equipe MOKILI TOUR vous rappelle pour confirmer. Le paiement se fait sur place.');
    }

    public static function createAppointment(Request $request, Vehicle $vehicle): VisitRequest
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published' && $vehicle->offer_type === 'vente', 404);
        if ($vehicle->is_demo) {
            throw ValidationException::withMessages(['visit_date' => "Annonce d'exemple : pas de rendez-vous possible pour ce vehicule."]);
        }
        $user = $request->user();
        $data = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'phone' => [$user?->phone ? 'nullable' : 'required', 'string', 'min:6', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'purpose' => ['required', Rule::in(array_keys(VisitRequest::SALE_PURPOSES))],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        return VisitRequest::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user?->id,
            'name' => $data['name'] ?? $user?->name,
            'phone' => $data['phone'] ?? $user?->phone,
            'email' => $data['email'] ?? $user?->email,
            'visit_date' => $data['visit_date'],
            'visit_time' => $data['visit_time'] ?? null,
            'service' => VisitRequest::SALE_PURPOSES[$data['purpose']],
            'vehicle' => $vehicle->title,
            'people' => 1,
            'formula' => 'individuel',
            'message' => $data['message'] ?? null,
            'status' => 'nouvelle',
        ]);
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
