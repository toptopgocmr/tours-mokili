<?php

namespace App\Services\Flights;

use App\Models\Booking;
use App\Models\Flights\FlightCommission;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOperation;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightPassenger;
use App\Models\Flights\FlightStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Flights\Suppliers\FlightSupplierManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creation du dossier (apres revalidation du prix) et reactions aux
 * evenements de paiement. Le paiement lui-meme reste gere par
 * App\Services\Payments\PaymentService via la reservation generique.
 */
class FlightOrderService
{
    public function __construct(
        protected FlightSupplierManager $suppliers,
        protected FlightStateMachine $machine,
        protected FlightNotifier $notifier,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $passengers
     * @param  array{email:string, phone?:?string}  $contact
     *
     * @throws FlightPriceChanged|FlightOfferUnavailable|ValidationException
     */
    public function createFromOffer(User $user, FlightOffer $offer, array $passengers, array $contact, ?float $acceptedPrice = null): FlightOrder
    {
        $search = $offer->search;
        $this->assertPassengerComposition($search->adults, $search->children, $search->infants, $passengers);

        // 1. Revalidation obligatoire aupres du fournisseur (offre expiree ou prix modifie).
        $driver = $this->suppliers->driver($offer->supplier);
        $check = $driver->revalidate($offer);

        if (! $check['available']) {
            FlightAudit::log('offer.unavailable', null, ['offer_id' => $offer->id], 'user:'.$user->id);
            throw new FlightOfferUnavailable('Cette offre n\'est plus disponible. Relancez la recherche.');
        }

        $cost = (float) $check['cost'];
        $price = FlightSearchService::customerPrice($cost);

        $offer->update([
            'supplier_cost' => $cost,
            'price_total' => $price,
            'price_confirmed_at' => now(),
            'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 20)),
        ]);

        if ($acceptedPrice !== null && abs($acceptedPrice - $price) > 0.5) {
            FlightAudit::log('offer.price_changed', null, ['offer_id' => $offer->id, 'old' => $acceptedPrice, 'new' => $price], 'user:'.$user->id);
            throw new FlightPriceChanged($price, $offer->currency);
        }

        $cap = (float) config('flights.max_order_amount', 0);
        if ($cap > 0 && $price > $cap) {
            throw ValidationException::withMessages([
                'offer' => 'Montant superieur au plafond autorise pendant la phase de lancement progressif.',
            ]);
        }

        return DB::transaction(function () use ($user, $offer, $passengers, $contact, $price, $cost) {
            $segments = $offer->itinerary['outbound'] ?? [];
            $firstSegment = $segments[0] ?? null;

            $order = FlightOrder::create([
                'user_id' => $user->id,
                'flight_offer_id' => $offer->id,
                'flight_supplier_id' => $offer->flight_supplier_id,
                'status' => FlightStatus::DRAFT,
                'price_total' => $price,
                'supplier_cost' => $cost,
                'commission_amount' => max($price - $cost, 0),
                'currency' => $offer->currency,
                'contact_email' => $contact['email'],
                'contact_phone' => $contact['phone'] ?? null,
            ]);

            foreach ($passengers as $p) {
                FlightPassenger::create([
                    'flight_order_id' => $order->id,
                    'type' => $p['type'] ?? 'adult',
                    'title' => $p['title'] ?? null,
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'birth_date' => $p['birth_date'],
                    'gender' => $p['gender'] ?? null,
                    'nationality' => isset($p['nationality']) ? strtoupper($p['nationality']) : null,
                    'passport_number' => $p['passport_number'] ?? null,
                    'passport_expiry' => $p['passport_expiry'] ?? null,
                ]);
            }

            // Enveloppe de paiement generique (checkout + Mobile Money existants).
            $booking = Booking::create([
                'user_id' => $user->id,
                'bookable_type' => FlightOrder::class,
                'bookable_id' => $order->id,
                'quantity' => count($passengers),
                'unit_price' => round($price / max(count($passengers), 1), 2),
                'total_amount' => $price,
                'currency' => $offer->currency,
                'starts_at' => $firstSegment['departure'] ?? null,
                'status' => 'awaiting_payment',
                'meta' => [
                    'type' => 'vol',
                    'route' => $offer->search->origin.' - '.$offer->search->destination,
                    'flight_order' => $order->reference,
                ],
            ]);
            $order->update(['booking_id' => $booking->id]);

            FlightCommission::create([
                'flight_order_id' => $order->id,
                'rate_percent' => (float) config('flights.margin_percent', 0),
                'amount' => max($price - $cost, 0),
                'currency' => $offer->currency,
                'status' => 'pending',
            ]);

            $this->machine->transition($order, FlightStatus::PRICE_CONFIRMED, 'user:'.$user->id, 'Prix revalide aupres du fournisseur');
            FlightAudit::log('order.created', $order, ['offer_id' => $offer->id, 'passengers' => count($passengers)], 'user:'.$user->id);

            return $order->fresh(['booking', 'passengers']);
        });
    }

    /** Paiement lance (PaymentService::collect) : PRICE_CONFIRMED -> PAYMENT_PENDING. */
    public function paymentInitiated(FlightOrder $order, Payment $payment): void
    {
        FlightOperation::create([
            'flight_order_id' => $order->id,
            'type' => 'payment',
            'idempotency_key' => 'pay:'.$payment->reference,
            'status' => 'started',
            'external_id' => $payment->gateway_reference,
        ]);

        if ($order->status === FlightStatus::PRICE_CONFIRMED) {
            $this->machine->transition($order, FlightStatus::PAYMENT_PENDING, 'system', 'Paiement initie ('.$payment->provider.')');
        }
    }

    /** Resultat du paiement (webhook, verification, back-office). Renvoie true si le traitement doit demarrer. */
    public function paymentSettled(FlightOrder $order, Payment $payment, bool $paid): bool
    {
        FlightOperation::query()
            ->where('idempotency_key', 'pay:'.$payment->reference)
            ->update(['status' => $paid ? 'succeeded' : 'failed']);

        if (! $paid) {
            if ($order->status === FlightStatus::PAYMENT_PENDING) {
                $this->machine->transition($order, FlightStatus::PRICE_CONFIRMED, 'webhook', 'Paiement refuse ou abandonne');
            }

            return false;
        }

        // Annexe : paiement tardif ou double sur un dossier qui ne l'attend plus = anomalie.
        if (! in_array($order->status, [FlightStatus::PRICE_CONFIRMED, FlightStatus::PAYMENT_PENDING], true)) {
            $order->update(['is_anomaly' => true, 'failure_reason' => 'Paiement recu alors que le dossier est au statut '.$order->status.'.']);
            FlightAudit::log('payment.unexpected', $order, ['payment_id' => $payment->id, 'status' => $order->status], 'webhook');
            app(FlightRefundService::class)->request($order, 'duplicate_payment', 'Paiement recu sur un dossier deja traite.', 'system', (float) $payment->amount, $payment, 0, 0, 0, false);

            return false;
        }

        if ($order->status === FlightStatus::PRICE_CONFIRMED) {
            $this->machine->transition($order, FlightStatus::PAYMENT_PENDING, 'webhook');
        }

        $this->machine->transition($order, FlightStatus::PAYMENT_CONFIRMED, 'webhook', 'Paiement confirme par le PSP ('.$payment->provider.')');
        $this->notifier->notify($order, 'payment_confirmed');

        return true;
    }

    /** Annulation par le client avant tout paiement confirme. */
    public function cancelUnpaid(FlightOrder $order, string $actor): FlightOrder
    {
        $order = $this->machine->transition($order, FlightStatus::CANCELLED, $actor, 'Annule avant paiement');
        $order->booking?->update(['status' => 'cancelled']);
        $this->notifier->notify($order, 'cancelled');

        return $order;
    }

    private function assertPassengerComposition(int $adults, int $children, int $infants, array $passengers): void
    {
        $count = fn (string $type) => count(array_filter($passengers, fn ($p) => ($p['type'] ?? 'adult') === $type));

        if ($count('adult') !== $adults || $count('child') !== $children || $count('infant') !== $infants) {
            throw ValidationException::withMessages([
                'passengers' => 'Le nombre de passagers ne correspond pas a la recherche.',
            ]);
        }
    }
}
