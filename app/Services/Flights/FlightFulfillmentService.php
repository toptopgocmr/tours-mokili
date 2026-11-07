<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightCommission;
use App\Models\Flights\FlightOperation;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Models\Flights\FlightTicket;
use App\Services\Flights\Suppliers\FlightSupplierDriver;
use App\Services\Flights\Suppliers\FlightSupplierManager;
use App\Services\Flights\Suppliers\SupplierRejectedException;
use App\Services\Flights\Suppliers\SupplierTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Apres paiement confirme : reservation (PNR) puis emission des billets.
 *
 * Garanties (cahier des charges, sections 11-12 et annexe) :
 *  - une cle d'idempotence unique par operation (reservation, emission) ;
 *  - AVANT toute (re)commande on interroge le fournisseur sur l'etat reel
 *    (findBooking / findTickets) : jamais de relance aveugle ;
 *  - un verrou empeche deux traitements simultanes du meme dossier ;
 *  - en cas d'echec definitif : dossier FAILED + anomalie + alerte, et
 *    demande de remboursement automatique (compensation).
 */
class FlightFulfillmentService
{
    public function __construct(
        protected FlightSupplierManager $suppliers,
        protected FlightStateMachine $machine,
        protected FlightNotifier $notifier,
        protected FlightRefundService $refunds,
    ) {}

    public function process(FlightOrder $order): FlightOrder
    {
        $lock = Cache::lock('flight-order:'.$order->id, 120);

        if (! $lock->get()) {
            return $order; // un autre traitement est deja en cours pour ce dossier
        }

        try {
            $order = $order->fresh();
            $driver = $this->suppliers->driver($order->supplier);

            if ($order->status === FlightStatus::PAYMENT_CONFIRMED) {
                $this->machine->transition($order, FlightStatus::BOOKING_PENDING, 'system');
            }

            if ($order->status === FlightStatus::BOOKING_PENDING) {
                if (! $this->book($order, $driver)) {
                    return $order->fresh();
                }
            }

            if ($order->status === FlightStatus::BOOKED) {
                $this->machine->transition($order, FlightStatus::TICKETING_PENDING, 'system');
            }

            if ($order->status === FlightStatus::TICKETING_PENDING) {
                $this->issue($order, $driver);
            }

            return $order->fresh();
        } finally {
            $lock->release();
        }
    }

    /** Reprise controlee d'un dossier FAILED par un administrateur. */
    public function retry(FlightOrder $order, string $actor): FlightOrder
    {
        if ($order->status !== FlightStatus::FAILED) {
            throw new InvalidFlightTransition('Seul un dossier en echec peut etre repris.');
        }

        // Pas de PNR : on reprend la reservation ; sinon l'emission (qui verifie d'abord les billets existants).
        $to = $order->pnr ? FlightStatus::TICKETING_PENDING : FlightStatus::BOOKING_PENDING;
        $order->update(['attempts' => 0]);
        $this->machine->transition($order, $to, $actor, 'Reprise controlee par un administrateur');
        FlightAudit::log('order.retry', $order, ['to' => $to], $actor);

        return $this->process($order->fresh());
    }

    private function book(FlightOrder $order, FlightSupplierDriver $driver): bool
    {
        $op = $this->operation($order, 'booking');

        // Deja reserve chez le fournisseur ? (reprise apres timeout / crash)
        $existing = $driver->findBooking($op->idempotency_key);

        if (! $existing) {
            try {
                $order->increment('attempts');
                $existing = $driver->createBooking($order, $op->idempotency_key);
            } catch (SupplierTimeoutException $e) {
                $existing = $driver->findBooking($op->idempotency_key); // etat reel avant toute relance
                if (! $existing) {
                    $op->update(['status' => 'unknown']);

                    return $this->failUnresolved($order, 'Delai depasse a la reservation, etat chez le fournisseur inconnu.');
                }
            } catch (SupplierRejectedException $e) {
                $op->update(['status' => 'failed', 'response' => ['error' => $e->getMessage()]]);

                return $this->failDefinitively($order, 'Reservation refusee : '.$e->getMessage());
            } catch (Throwable $e) {
                report($e);
                $op->update(['status' => 'unknown']);

                return $this->failUnresolved($order, 'Erreur technique a la reservation.');
            }
        }

        $op->update(['status' => 'succeeded', 'external_id' => $existing['pnr'], 'response' => ['pnr' => $existing['pnr']]]);
        $order->update(['pnr' => $existing['pnr'], 'supplier_order_id' => $existing['supplier_order_id'] ?? null]);
        $this->machine->transition($order, FlightStatus::BOOKED, 'system', 'PNR '.$existing['pnr']);
        $this->notifier->notify($order, 'booked');

        return true;
    }

    private function issue(FlightOrder $order, FlightSupplierDriver $driver): void
    {
        $op = $this->operation($order, 'ticketing');

        // Billets deja emis ? Ne JAMAIS relancer l'emission a l'aveugle.
        $tickets = $driver->findTickets($op->idempotency_key);

        if (! $tickets) {
            try {
                $order->increment('attempts');
                $tickets = $driver->issueTickets($order->load('passengers'), $op->idempotency_key);
            } catch (SupplierTimeoutException $e) {
                $tickets = $driver->findTickets($op->idempotency_key);
                if (! $tickets) {
                    $op->update(['status' => 'unknown']);
                    $this->failUnresolved($order, 'Delai depasse a l\'emission, etat reel inconnu : verification manuelle requise (ne pas relancer a l\'aveugle).');

                    return;
                }
            } catch (SupplierRejectedException $e) {
                $op->update(['status' => 'failed', 'response' => ['error' => $e->getMessage()]]);
                $this->failDefinitively($order, 'Emission refusee : '.$e->getMessage());

                return;
            } catch (Throwable $e) {
                report($e);
                $op->update(['status' => 'unknown']);
                $this->failUnresolved($order, 'Erreur technique a l\'emission.');

                return;
            }
        }

        foreach ($order->passengers as $passenger) {
            if (! isset($tickets[$passenger->id])) {
                $this->failUnresolved($order, 'Billet manquant pour un passager (verification manuelle).');

                return;
            }

            FlightTicket::firstOrCreate(
                ['ticket_number' => $tickets[$passenger->id]],
                ['flight_order_id' => $order->id, 'flight_passenger_id' => $passenger->id, 'status' => 'issued', 'issued_at' => now()]
            );
        }

        $op->update(['status' => 'succeeded', 'response' => ['tickets' => count($tickets)]]);
        $this->machine->transition($order, FlightStatus::TICKETED, 'system', 'Billets emis');
        FlightCommission::where('flight_order_id', $order->id)->where('status', 'pending')->update(['status' => 'earned']);
        $this->notifier->notify($order, 'ticketed');
    }

    /** Une operation externe = une cle d'idempotence, creee une seule fois et reutilisee a chaque reprise. */
    private function operation(FlightOrder $order, string $type): FlightOperation
    {
        return FlightOperation::query()->firstOrCreate(
            ['flight_order_id' => $order->id, 'type' => $type],
            ['idempotency_key' => $type.':'.$order->reference.':'.Str::uuid(), 'status' => 'started']
        );
    }

    /** Echec dont l'issue est incertaine : anomalie, alerte, intervention manuelle (pas de relance aveugle). */
    private function failUnresolved(FlightOrder $order, string $reason): bool
    {
        $this->markAnomaly($order, $reason);

        return false;
    }

    /** Refus definitif du fournisseur : anomalie + compensation (remboursement). */
    private function failDefinitively(FlightOrder $order, string $reason): bool
    {
        $this->markAnomaly($order, $reason);
        $this->refunds->request($order->fresh(), 'ticketing_failed', $reason, 'system');

        return false;
    }

    private function markAnomaly(FlightOrder $order, string $reason): void
    {
        $order->update(['is_anomaly' => true, 'failure_reason' => $reason]);
        $this->machine->transition($order, FlightStatus::FAILED, 'system', $reason);
        FlightAudit::log('order.anomaly', $order, ['reason' => $reason, 'attempts' => $order->attempts]);
        Log::critical('Dossier de vol en anomalie', ['order' => $order->reference, 'reason' => $reason]);
        $this->notifier->notify($order, 'failed');
    }
}
