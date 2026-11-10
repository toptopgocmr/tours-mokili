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

    public const ID_TYPES = ['cni' => "Carte nationale d'identite", 'passeport' => 'Passeport', 'carte_sejour' => 'Carte de sejour', 'carte_consulaire' => 'Carte consulaire'];

    public const DRIVER_AGES = ['18-24' => '18 - 24 ans', '25-29' => '25 - 29 ans', '30+' => '30 ans et plus'];

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
            'return_time' => ['nullable', 'date_format:H:i'],
            'return_elsewhere' => ['boolean'],
            'return_place' => ['nullable', 'required_if:return_elsewhere,true', 'string', 'max:255'],
            'driver_age' => ['nullable', 'in:'.implode(',', array_keys(self::DRIVER_AGES))],
            'pickup_mode' => ['required', 'in:'.implode(',', array_keys(self::PICKUP))],
            'pickup_address' => ['nullable', 'required_if:pickup_mode,livraison', 'string', 'max:255'],
            'with_driver' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Piece d'identite (toujours) et permis de conduire (sauf location avec chauffeur).
            'id_type' => ['required', 'in:'.implode(',', array_keys(self::ID_TYPES))],
            'id_number' => ['required', 'string', 'max:60'],
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:6144'],
            'license_number' => ['required_unless:with_driver,1,true', 'nullable', 'string', 'max:60'],
            'license_date' => ['required_unless:with_driver,1,true', 'nullable', 'date', 'before:today'],
            'license_document' => ['required_unless:with_driver,1,true', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:6144'],
        ], ['pickup_address.required_if' => 'Indiquez l\'adresse de livraison.', 'return_place.required_if' => 'Indiquez le lieu de retour.',
            'id_document.required' => "Ajoutez une photo de votre piece d'identite.",
            'license_number.required_unless' => 'Indiquez le numero de votre permis de conduire.',
            'license_date.required_unless' => "Indiquez la date d'obtention du permis.",
            'license_document.required_unless' => 'Ajoutez une photo de votre permis de conduire.',
        ]);
        // Documents prives (disque local, jamais publics).
        $folder = 'documents/location/'.$request->user()->id;
        $idPath = $request->file('id_document')->store($folder, 'local');
        $licensePath = $request->hasFile('license_document') ? $request->file('license_document')->store($folder, 'local') : null;

        $start = Carbon::parse($data['starts_at'].' '.($data['pickup_time'] ?? '09:00'));
        $end = Carbon::parse($data['ends_at'].' '.($data['return_time'] ?? $data['pickup_time'] ?? '09:00'));

        $booking = DB::transaction(function () use ($vehicle, $start, $end, $data, $request, $idPath, $licensePath) {
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
                    'return_place' => ! empty($data['return_elsewhere']) ? ($data['return_place'] ?? null) : null,
                    'driver_age' => $data['driver_age'] ?? null,
                    'driver' => [
                        'id_type' => $data['id_type'],
                        'id_type_label' => self::ID_TYPES[$data['id_type']],
                        'id_number' => $data['id_number'],
                        'id_document' => $idPath,
                        'license_number' => $data['license_number'] ?? null,
                        'license_date' => $data['license_date'] ?? null,
                        'license_document' => $licensePath,
                    ],
                ],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('checkout.show', $booking)
            ->with('success', 'Vehicule bloque pour vous pendant '.Availability::HOLD_MINUTES.' minutes : payez pour confirmer la location.');
    }

    /** Document du conducteur : visible par le client, le loueur et l'equipe MOKILI TOUR. */
    public function document(Request $request, Booking $booking, string $doc)
    {
        abort_unless(in_array($doc, ['id_document', 'license_document'], true), 404);
        $user = $request->user();
        $vehicle = $booking->bookable;
        abort_unless($booking->user_id === $user->id || in_array($user->role, ['admin', 'agent'], true)
            || ($vehicle instanceof Vehicle && $vehicle->owner_id === $user->id), 403);
        $path = $booking->meta['driver'][$doc] ?? null;
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path);
    }

    protected function ensureRentable(Vehicle $vehicle): void
    {
        abort_unless($vehicle->is_active && $vehicle->status === 'published' && $vehicle->offer_type === 'location' && $vehicle->price_per_day, 404);
    }
}
