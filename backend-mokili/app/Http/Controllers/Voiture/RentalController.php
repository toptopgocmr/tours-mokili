<?php

namespace App\Http\Controllers\Voiture;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use App\Services\Voiture\VehicleAvailability as Availability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** VOITURE - location : verification de disponibilite, reservation puis paiement Mobile Money. */
class RentalController extends Controller
{
    public const PICKUP = [
        'agence' => "Retrait a l'agence",
        'livraison' => 'Livraison a mon adresse',
        'aeroport' => "Remise a l'aeroport",
    ];

    public function availability(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureRentable($vehicle);
        $data = $request->validate([
            'start' => ['required', 'date', 'after_or_equal:today'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);
        [$start, $end] = [Carbon::parse($data['start']), Carbon::parse($data['end'])];
        $days = Availability::days($start, $end);
        $available = Availability::isAvailable($vehicle, $start, $end);
        $unit = $vehicle->discountedDayPrice();

        return response()->json([
            'available' => $available,
            'days' => $days,
            'unit_price' => $unit,
            'total' => $unit * $days,
            'currency' => $vehicle->currency,
            'next_free' => $available ? null : Availability::nextFree($vehicle, $start, $days),
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->ensureRentable($vehicle);
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'pickup_mode' => ['required', 'in:'.implode(',', array_keys(self::PICKUP))],
            'pickup_address' => ['nullable', 'required_if:pickup_mode,livraison', 'string', 'max:255'],
            'with_driver' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['pickup_address.required_if' => 'Indiquez l\'adresse de livraison.']);

        $start = Carbon::parse($data['starts_at'].' '.($data['pickup_time'] ?? '09:00'));
        $end = Carbon::parse($data['ends_at'].' '.($data['pickup_time'] ?? '09:00'));

        $booking = DB::transaction(function () use ($vehicle, $start, $end, $data, $request) {
            // Verrou sur le vehicule : deux clients ne peuvent pas prendre les memes dates.
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->first();
            if (! Availability::isAvailable($vehicle, $start, $end)) {
                throw ValidationException::withMessages(['starts_at' => 'Ce vehicule est deja reserve sur ces dates. Choisissez d\'autres dates.']);
            }
            $days = Availability::days($start, $end);
            $unit = $vehicle->discountedDayPrice();

            return Booking::create([
                'user_id' => $request->user()->id,
                'bookable_type' => Vehicle::class,
                'bookable_id' => $vehicle->id,
                'quantity' => $days,
                'unit_price' => $unit,
                'total_amount' => $unit * $days,
                'currency' => $vehicle->currency,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'awaiting_payment',
                'meta' => [
                    'kind' => 'location_voiture',
                    'city' => $vehicle->city,
                    'pickup_mode' => $data['pickup_mode'],
                    'pickup_label' => self::PICKUP[$data['pickup_mode']],
                    'pickup_address' => $data['pickup_address'] ?? null,
                    'with_driver' => (bool) ($data['with_driver'] ?? false),
                ],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Vehicule bloque pour vous pendant '.Availability::HOLD_MINUTES.' minutes : payez pour confirmer la location.');
    }

    protected function ensureRentable(Vehicle $vehicle): void
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published' && $vehicle->offer_type === 'location' && $vehicle->price_per_day, 404);
    }
}
