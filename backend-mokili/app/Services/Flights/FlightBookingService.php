<?php

namespace App\Services\Flights;

use App\Models\Booking;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSupplierLog;
use App\Models\User;
use App\Services\Flights\Data\SearchCriteria;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Creation du dossier (passagers + revalidation du prix) et liaison avec le
 * checkout commun. Aucun paiement n'est demande sur un prix non revalide.
 */
class FlightBookingService
{
    public function __construct(
        protected FlightSupplierManager $suppliers,
        protected FlightPricing $pricing,
        protected FlightStateMachine $machine,
        protected FlightSearchService $searchService,
    ) {}

    /**
     * @param  array  $data  passengers[], contact_name, contact_email, contact_phone, expected_total
     *
     * @throws FlightException si l'offre n'est plus disponible ou si le prix a change
     */
    public function createFromOffer(FlightOffer $offer, User $user, array $data): FlightBooking
    {
        $search = $offer->search;
        $this->assertPassengersMatch($search->adults, $search->children, $search->infants, $data['passengers']);
        $this->assertDocuments($data['passengers'], $offer);

        // 1. Revalidation aupres du fournisseur (prix + disponibilite).
        $changed = $this->revalidateOffer($offer);
        $expected = isset($data['expected_total']) ? round((float) $data['expected_total'], 2) : null;
        if ($changed || ($expected !== null && abs($expected - (float) $offer->total_amount) > 0.01)) {
            throw new FlightException(
                'Le prix de ce vol a change : '.number_format((float) $offer->total_amount, 0, ',', ' ').' '.$offer->currency.'. Verifiez puis confirmez le nouveau prix.',
                'price_changed',
                ['total_amount' => (float) $offer->total_amount],
            );
        }

        // 2. Dossier + passagers + reservation de paiement, en une transaction.
        return DB::transaction(function () use ($offer, $user, $data, $search) {
            $first = $offer->slices[0] ?? [];
            $last = $offer->slices[1] ?? null;

            $flight = FlightBooking::create([
                'user_id' => $user->id,
                'flight_offer_id' => $offer->id,
                'flight_supplier_id' => $offer->flight_supplier_id,
                'status' => FlightStatus::DRAFT,
                'contact_name' => $data['contact_name'] ?? $user->name,
                'contact_email' => $data['contact_email'] ?? $user->email,
                'contact_phone' => $data['contact_phone'] ?? $user->phone,
                'trip_type' => $search->trip_type,
                'origin' => $search->origin,
                'destination' => $search->destination,
                'depart_date' => $search->depart_date,
                'return_date' => $search->return_date,
                'itinerary' => $offer->slices,
                'fare_rules' => $offer->fare_rules,
                'passenger_count' => $search->passengerCount(),
                'supplier_cost' => $offer->supplier_amount,
                'markup_amount' => $offer->markup_amount,
                'service_fee' => $offer->service_fee,
                'commission_amount' => 0,
                'total_amount' => $offer->total_amount,
                'currency' => $offer->currency,
                'ticketing_deadline' => $offer->raw['ticketing_deadline'] ?? null,
            ]);

            \App\Models\Flights\FlightStatusHistory::create([
                'flight_booking_id' => $flight->id, 'from_status' => null, 'to_status' => FlightStatus::DRAFT,
                'actor_type' => 'client', 'actor_id' => $user->id, 'note' => 'Dossier cree',
            ]);

            foreach ($data['passengers'] as $p) {
                $flight->passengers()->create([
                    'type' => $p['type'],
                    'title' => $p['title'] ?? null,
                    'first_name' => trim($p['first_name']),
                    'last_name' => trim($p['last_name']),
                    'gender' => $p['gender'] ?? null,
                    'birth_date' => $p['birth_date'] ?? null,
                    'nationality' => isset($p['nationality']) ? strtoupper($p['nationality']) : null,
                    'document_type' => $p['document_type'] ?? null,
                    'document_number' => isset($p['document_number']) ? strtoupper(preg_replace('/\s+/', '', $p['document_number'])) : null,
                    'document_expiry' => $p['document_expiry'] ?? null,
                    'document_country' => isset($p['document_country']) ? strtoupper($p['document_country']) : null,
                ]);
            }

            $this->machine->transition($flight, FlightStatus::SEARCHED, note: 'Offre selectionnee', actorType: 'client');
            $this->machine->transition($flight, FlightStatus::PRICE_CONFIRMED, [
                'price_confirmed_at' => $offer->price_confirmed_at ?? now(),
            ], 'Prix revalide aupres du fournisseur', ['total' => (float) $offer->total_amount], 'system');

            $this->attachPaymentBooking($flight);

            return $flight->fresh(['passengers', 'booking']);
        });
    }

    /**
     * Revalide le prix d'une offre aupres du fournisseur et met l'offre a
     * jour. Renvoie true si le prix client a change.
     */
    public function revalidateOffer(FlightOffer $offer, ?int $flightBookingId = null): bool
    {
        $search = $offer->search;
        $criteria = SearchCriteria::fromArray($search->only(['origin', 'destination', 'trip_type', 'adults', 'children', 'infants', 'cabin'])
            + ['depart_date' => $search->depart_date->toDateString(), 'return_date' => $search->return_date?->toDateString()]);

        $started = microtime(true);
        try {
            $data = $this->suppliers->driver($offer->supplier)->price($offer, $criteria);
        } catch (Throwable $e) {
            report($e);
            throw new FlightException('Impossible de confirmer le prix aupres de la compagnie pour le moment. Reessayez dans quelques minutes.', 'supplier_error');
        } finally {
            FlightSupplierLog::create([
                'flight_booking_id' => $flightBookingId,
                'flight_supplier_id' => $offer->flight_supplier_id,
                'operation' => 'price',
                'success' => isset($data) && $data !== null,
                'outcome' => isset($data) ? ($data ? 'available' : 'unavailable') : 'error',
                'external_ref' => $offer->supplier_offer_id,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        }

        if (! $data) {
            throw new FlightException("Ce vol n'est plus disponible a ce tarif. Lancez une nouvelle recherche.", 'unavailable');
        }

        $quote = $this->pricing->quote($data);
        $changed = abs($quote['total_amount'] - (float) $offer->total_amount) > 0.01;

        $offer->update([
            'passengers' => $this->searchService->displayLines($data, $quote),
            'slices' => $data->slices,
            'fare_rules' => $data->fareRules,
            'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 30)),
            'price_confirmed_at' => now(),
        ] + $quote);

        return $changed;
    }

    /**
     * Appele par PaymentService::collect() avant toute demande de paiement :
     * revalide si le prix a expire et passe le dossier en PAYMENT_PENDING.
     */
    public function beforePayment(Booking $booking): void
    {
        $flight = $booking->bookable;
        if (! $flight instanceof FlightBooking) {
            return;
        }

        if (! in_array($flight->status, [FlightStatus::PRICE_CONFIRMED, FlightStatus::PAYMENT_PENDING], true)) {
            throw new FlightException('Ce dossier ne peut pas etre paye dans son etat actuel ('.$flight->status_label.').', 'invalid_state');
        }

        // Pas de double paiement : une demande deja en cours doit aboutir ou echouer.
        $pending = $booking->payments()->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>=', now()->subMinutes(10))->exists();
        if ($pending) {
            throw new FlightException('Un paiement est deja en cours pour ce dossier. Validez-le sur votre telephone ou patientez quelques minutes.', 'payment_in_progress');
        }

        $ttl = (int) config('flights.offer_ttl_minutes', 30);
        if (! $flight->price_confirmed_at || $flight->price_confirmed_at->lt(now()->subMinutes($ttl))) {
            $offer = $flight->offer;
            try {
                $changed = $offer ? $this->revalidateOffer($offer, $flight->id) : true;
            } catch (FlightException $e) {
                if ($e->reason === 'unavailable') {
                    $this->machine->transition($flight, FlightStatus::CANCELLED, ['cancelled_at' => now()], 'Offre plus disponible avant paiement', actorType: 'system');
                    $booking->update(['status' => 'cancelled']);
                }
                throw $e;
            }

            if ($changed) {
                $this->machine->transition($flight, FlightStatus::SEARCHED, $this->amounts($offer), 'Prix modifie par le fournisseur avant paiement', ['total' => (float) $offer->total_amount], 'system');
                $booking->update(['total_amount' => $offer->total_amount, 'unit_price' => $offer->total_amount]);
                throw new FlightException('Le prix de ce vol a change. Confirmez le nouveau prix avant de payer.', 'price_changed', ['total_amount' => (float) $offer->total_amount]);
            }
            $flight->update(['price_confirmed_at' => now()]);
        }

        $this->machine->transition($flight, FlightStatus::PAYMENT_PENDING, note: 'Paiement initie', actorType: 'client');
    }

    /** Le client accepte le nouveau prix (dossier en SEARCHED apres changement). */
    public function confirmNewPrice(FlightBooking $flight): FlightBooking
    {
        if ($flight->status !== FlightStatus::SEARCHED || ! $flight->offer) {
            throw new FlightException("Ce dossier n'attend pas de confirmation de prix.", 'invalid_state');
        }
        $this->revalidateOffer($flight->offer, $flight->id);
        $offer = $flight->offer->fresh();

        $this->machine->transition($flight, FlightStatus::PRICE_CONFIRMED, $this->amounts($offer) + ['price_confirmed_at' => now()], 'Nouveau prix accepte par le client', ['total' => (float) $offer->total_amount], 'client');
        $flight->booking?->update(['total_amount' => $offer->total_amount, 'unit_price' => $offer->total_amount, 'status' => 'awaiting_payment']);

        return $flight->fresh();
    }

    public function cancelUnpaid(FlightBooking $flight, string $reason = 'Annule par le client'): void
    {
        if ($flight->isPaid()) {
            throw new FlightException('Ce dossier est paye : faites une demande d\'annulation / remboursement.', 'paid');
        }
        $this->machine->transition($flight, FlightStatus::CANCELLED, ['cancelled_at' => now()], $reason);
        $flight->booking?->update(['status' => 'cancelled']);
    }

    protected function attachPaymentBooking(FlightBooking $flight): Booking
    {
        $booking = Booking::create([
            'user_id' => $flight->user_id,
            'bookable_type' => FlightBooking::class,
            'bookable_id' => $flight->id,
            'quantity' => 1,
            'unit_price' => $flight->total_amount,
            'total_amount' => $flight->total_amount,
            'currency' => $flight->currency,
            'starts_at' => $flight->depart_date,
            'ends_at' => $flight->return_date,
            'status' => 'awaiting_payment',
            'meta' => [
                'type' => 'vol',
                'destination' => $flight->destination,
                'flight_reference' => $flight->reference,
                'passengers' => $flight->passenger_count,
            ],
        ]);

        $flight->update(['booking_id' => $booking->id]);

        return $booking;
    }

    protected function amounts(FlightOffer $offer): array
    {
        return [
            'supplier_cost' => $offer->supplier_amount,
            'markup_amount' => $offer->markup_amount,
            'service_fee' => $offer->service_fee,
            'total_amount' => $offer->total_amount,
            'itinerary' => $offer->slices,
            'fare_rules' => $offer->fare_rules,
        ];
    }

    protected function assertPassengersMatch(int $adults, int $children, int $infants, array $passengers): void
    {
        $counts = array_count_values(array_column($passengers, 'type'));
        if (($counts['ADT'] ?? 0) !== $adults || ($counts['CHD'] ?? 0) !== $children || ($counts['INF'] ?? 0) !== $infants) {
            throw new FlightException('Le nombre de passagers ne correspond pas a la recherche.', 'passengers');
        }
    }

    /** Vol international : passeport valide au-dela de la date de retour. */
    protected function assertDocuments(array $passengers, FlightOffer $offer): void
    {
        $search = $offer->search;
        $countries = \App\Models\Flights\Airport::whereIn('iata_code', [$search->origin, $search->destination])->pluck('country_code')->unique();
        if ($countries->count() < 2) {
            return; // vol domestique
        }
        $lastDate = ($search->return_date ?? $search->depart_date)->copy();
        foreach ($passengers as $p) {
            if (empty($p['document_number']) || empty($p['document_expiry'])) {
                throw new FlightException('Vol international : numero et date d\'expiration du passeport obligatoires pour '.$p['first_name'].'.', 'documents');
            }
            if (\Carbon\Carbon::parse($p['document_expiry'])->lt($lastDate)) {
                throw new FlightException('Le passeport de '.$p['first_name'].' expire avant la fin du voyage.', 'documents');
            }
        }
    }
}
