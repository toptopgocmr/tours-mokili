<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightAfterSale;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightIdempotencyKey;
use App\Models\Flights\FlightSupplierLog;
use App\Models\Flights\FlightTicket;
use App\Services\Flights\Data\SupplierResult;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Apres paiement confirme : reservation (PNR) puis emission, avec
 * idempotence, verification de l'etat reel en cas de timeout, et procedure
 * de compensation si la reservation ou l'emission echoue definitivement
 * (annexe du cahier des charges).
 */
class FlightFulfillmentService
{
    public function __construct(
        protected FlightSupplierManager $suppliers,
        protected FlightStateMachine $machine,
        protected FlightNotifier $notifier,
    ) {}

    /** Fait avancer un dossier paye aussi loin que possible. */
    public function process(FlightBooking $flight): FlightBooking
    {
        $flight->refresh();

        if ($flight->status === FlightStatus::PAYMENT_CONFIRMED) {
            $this->machine->transition($flight, FlightStatus::BOOKING_PENDING, note: 'Demande de reservation au fournisseur', actorType: 'system');
            $result = $this->runOnce($flight, 'book', fn ($driver, $key) => $driver->book($flight, $key));
            $this->applyBookResult($flight, $result);
            $flight->refresh();
        }

        if ($flight->status === FlightStatus::BOOKED) {
            $this->machine->transition($flight, FlightStatus::TICKETING_PENDING, note: "Demande d'emission", actorType: 'system');
            $result = $this->runOnce($flight, 'issue', fn ($driver, $key) => $driver->issue($flight, $key));
            $this->applyIssueResult($flight, $result);
            $flight->refresh();
        }

        return $flight;
    }

    /**
     * Interroge le fournisseur sur l'etat reel d'un dossier en attente ou
     * en doute (timeout) et applique ce qui est confirme. Ne relance une
     * operation que si le fournisseur confirme qu'elle n'a pas abouti.
     */
    public function reconcile(FlightBooking $flight): FlightBooking
    {
        $flight->refresh();
        if (! in_array($flight->status, [FlightStatus::BOOKING_PENDING, FlightStatus::TICKETING_PENDING, FlightStatus::FAILED, FlightStatus::PAYMENT_CONFIRMED], true)) {
            return $flight;
        }
        if ($flight->status === FlightStatus::PAYMENT_CONFIRMED) {
            return $this->process($flight);
        }

        $result = $this->call($flight, 'retrieve', null, fn ($driver) => $driver->retrieve($flight));
        $flight->update(['last_supplier_check_at' => now()]);
        if ($result->status === SupplierResult::UNKNOWN) {
            return $flight;
        }

        if ($flight->status === FlightStatus::BOOKING_PENDING || ($flight->status === FlightStatus::FAILED && ! $flight->booked_at)) {
            if ($result->pnr && $result->bookingStatus === 'booked') {
                $this->markKey($flight, 'book', 'completed', $result);
                $this->recordBooked($flight, $result->pnr, $result->supplierReference, 'Reservation confirmee apres verification fournisseur');

                return $this->process($flight->fresh());
            }
            // Le fournisseur confirme que rien n'a ete cree : relance controlee.
            if ($result->bookingStatus === 'none' && $this->keyState($flight, 'book') === 'unknown') {
                $this->markKey($flight, 'book', 'retry', $result);
                if ($flight->status === FlightStatus::BOOKING_PENDING) {
                    $retry = $this->runOnce($flight, 'book', fn ($driver, $key) => $driver->book($flight, $key));
                    $this->applyBookResult($flight, $retry);

                    return $this->process($flight->fresh());
                }
            }

            return $flight->fresh();
        }

        if ($flight->status === FlightStatus::TICKETING_PENDING || ($flight->status === FlightStatus::FAILED && $flight->booked_at && ! $flight->ticketed_at)) {
            if ($result->ticketStatus === 'issued' && $result->tickets) {
                $this->markKey($flight, 'issue', 'completed', $result);
                $this->recordTickets($flight, $result->tickets, 'Billets confirmes apres verification fournisseur');
            } elseif ($result->ticketStatus === 'none' && $this->keyState($flight, 'issue') === 'unknown' && $flight->status === FlightStatus::TICKETING_PENDING) {
                $this->markKey($flight, 'issue', 'retry', $result);
                $retry = $this->runOnce($flight, 'issue', fn ($driver, $key) => $driver->issue($flight, $key));
                $this->applyIssueResult($flight, $retry);
            }
        }

        return $flight->fresh();
    }

    /** Saisie manuelle du PNR par l'equipe (fournisseur sans API). */
    public function recordBooked(FlightBooking $flight, string $pnr, ?string $supplierRef = null, string $note = 'PNR saisi par l\'equipe billetterie', ?string $deadline = null): FlightBooking
    {
        $this->machine->transition($flight, FlightStatus::BOOKED, array_filter([
            'pnr' => strtoupper(trim($pnr)),
            'supplier_booking_ref' => $supplierRef,
            'booked_at' => now(),
            'ticketing_deadline' => $deadline,
            'needs_attention' => false,
            'attention_reason' => null,
            'failure_reason' => null,
        ], fn ($v) => $v !== null) + ['needs_attention' => false], $note);
        $this->markKey($flight, 'book', 'completed', SupplierResult::success($pnr, $supplierRef));
        $this->notifier->statusChanged($flight->fresh());

        return $flight->fresh();
    }

    /**
     * Enregistre les numeros de billets (un par passager) et passe le
     * dossier en TICKETED. C'est le seul moment ou le client est informe
     * que son billet est emis.
     *
     * @param  array  $tickets  [passenger_id => ticket_number]
     */
    public function recordTickets(FlightBooking $flight, array $tickets, string $note = 'Billets saisis par l\'equipe billetterie'): FlightBooking
    {
        $passengerIds = $flight->passengers()->pluck('id')->all();
        $tickets = array_filter(array_map(fn ($n) => strtoupper(trim((string) $n)), $tickets));
        $missing = array_diff($passengerIds, array_keys($tickets));
        if ($missing) {
            throw new FlightException('Un numero de billet est requis pour chaque passager.', 'tickets');
        }
        if (! $flight->pnr) {
            throw new FlightException('Saisissez d\'abord le PNR de la reservation.', 'tickets');
        }

        DB::transaction(function () use ($flight, $tickets, $note) {
            foreach ($tickets as $passengerId => $number) {
                FlightTicket::updateOrCreate(
                    ['flight_booking_id' => $flight->id, 'flight_passenger_id' => $passengerId],
                    ['ticket_number' => $number, 'status' => 'issued', 'issued_at' => now()],
                );
            }
            $this->machine->transition($flight, FlightStatus::TICKETED, [
                'ticketed_at' => now(), 'needs_attention' => false, 'attention_reason' => null, 'failure_reason' => null,
            ], $note);
        });
        $this->markKey($flight, 'issue', 'completed', SupplierResult::success($flight->pnr, null, $tickets));
        $flight->booking?->update(['status' => 'completed']);
        $this->notifier->statusChanged($flight->fresh());

        return $flight->fresh();
    }

    public function markFailed(FlightBooking $flight, string $reason): FlightBooking
    {
        $this->machine->transition($flight, FlightStatus::FAILED, [
            'failure_reason' => mb_substr($reason, 0, 250),
            'needs_attention' => $flight->isPaid(),
            'attention_reason' => $flight->isPaid() ? 'Paiement encaisse : compensation ou remboursement a traiter' : null,
        ], $reason);
        $this->notifier->statusChanged($flight->fresh());
        if ($flight->isPaid()) {
            $this->notifier->alertOps($flight->fresh(), 'Echec apres paiement : '.$reason);
        }

        return $flight->fresh();
    }

    /** Lance la procedure de remboursement (dossier paye). */
    public function startRefund(FlightBooking $flight, ?float $amount, string $reason, ?FlightAfterSale $request = null): FlightBooking
    {
        if (! $flight->isPaid()) {
            throw new FlightException('Ce dossier n\'a pas ete paye.', 'not_paid');
        }
        $amount ??= (float) $flight->total_amount;
        if ($amount <= 0 || $amount > (float) $flight->total_amount) {
            throw new FlightException('Montant de remboursement invalide.', 'amount');
        }

        $this->machine->transition($flight, FlightStatus::REFUND_PENDING, ['refund_status' => 'pending'], $reason, ['amount' => $amount]);
        $request ??= $flight->afterSales()->where('type', '!=', 'change')->whereNotIn('status', ['rejected', 'processed'])->first();
        if ($request) {
            $request->update(['status' => 'approved', 'refundable_amount' => $amount, 'refund_status' => 'pending', 'processed_by' => auth()->id(), 'processed_at' => now()]);
        } else {
            $flight->afterSales()->create([
                'type' => 'refund', 'reason' => $reason, 'status' => 'approved', 'refundable_amount' => $amount,
                'refund_status' => 'pending', 'processed_by' => auth()->id(), 'processed_at' => now(),
            ]);
        }
        $this->notifier->statusChanged($flight->fresh());

        return $flight->fresh();
    }

    /** Remboursement effectue (Mobile Money / virement) : confirme et rapproche. */
    public function confirmRefund(FlightBooking $flight, float $amount, ?string $reference, ?string $note = null): FlightBooking
    {
        DB::transaction(function () use ($flight, $amount, $reference, $note) {
            $this->machine->transition($flight, FlightStatus::REFUNDED, [
                'refund_status' => 'refunded',
                'refunded_amount' => $amount,
                'refunded_at' => now(),
                'needs_attention' => false,
                'attention_reason' => null,
            ], $note ?? 'Remboursement confirme', ['amount' => $amount, 'reference' => $reference]);

            $flight->afterSales()->where('refund_status', 'pending')->update([
                'refund_status' => 'refunded', 'status' => 'processed', 'refund_reference' => $reference, 'processed_at' => now(),
            ]);

            $payment = $flight->booking?->payments()->where('status', 'paid')->latest()->first();
            $payment?->update(['status' => 'refunded', 'refunded_at' => now(), 'refund_reason' => 'Billetterie '.$flight->reference.($reference ? ' - '.$reference : '')]);
            $flight->booking?->update(['status' => 'refunded']);
        });
        $this->notifier->statusChanged($flight->fresh());

        return $flight->fresh();
    }

    protected function applyBookResult(FlightBooking $flight, SupplierResult $result): void
    {
        match ($result->status) {
            SupplierResult::SUCCESS => $this->recordBooked($flight, (string) $result->pnr, $result->supplierReference, 'Reservation confirmee par le fournisseur', $result->raw['ticketing_deadline'] ?? null),
            SupplierResult::FAILED => $this->markFailed($flight, 'Reservation refusee : '.($result->message ?? 'sans motif')),
            SupplierResult::PENDING => $this->flag($flight, 'A reserver : '.($result->message ?? 'traitement manuel'), true),
            default => $this->flag($flight, 'Reservation sans reponse fournisseur : verification necessaire avant relance', true),
        };
    }

    protected function applyIssueResult(FlightBooking $flight, SupplierResult $result): void
    {
        match ($result->status) {
            SupplierResult::SUCCESS => $result->tickets
                ? $this->recordTickets($flight, $result->tickets, 'Billets emis par le fournisseur')
                : $this->flag($flight, 'Emission confirmee sans numeros de billets : a verifier', true),
            SupplierResult::FAILED => $this->markFailed($flight, 'Emission refusee : '.($result->message ?? 'sans motif')),
            SupplierResult::PENDING => $this->flag($flight, 'A emettre : '.($result->message ?? 'traitement manuel'), true),
            default => $this->flag($flight, 'Emission sans reponse fournisseur : ne pas relancer avant verification', true),
        };
    }

    protected function flag(FlightBooking $flight, string $reason, bool $alert): void
    {
        $flight->update(['needs_attention' => true, 'attention_reason' => mb_substr($reason, 0, 250)]);
        if ($alert) {
            $this->notifier->alertOps($flight->fresh(), $reason);
        }
    }

    /**
     * Execute une operation fournisseur une seule fois par cle
     * d'idempotence. Si une tentative precedente est restee sans reponse,
     * on interroge l'etat reel au lieu de relancer.
     */
    protected function runOnce(FlightBooking $flight, string $operation, callable $fn): SupplierResult
    {
        $key = "{$operation}:{$flight->id}";
        $existing = FlightIdempotencyKey::where('key', $key)->first();

        if ($existing && $existing->state === 'completed' && $existing->result) {
            return $this->resultFromArray($existing->result);
        }
        if ($existing && in_array($existing->state, ['started', 'unknown', 'pending'], true)) {
            $status = $this->call($flight, 'retrieve', null, fn ($driver) => $driver->retrieve($flight));
            $done = $operation === 'book' ? ($status->bookingStatus === 'booked' && $status->pnr) : ($status->ticketStatus === 'issued' && $status->tickets);
            if ($done) {
                return SupplierResult::success($status->pnr, $status->supplierReference, $status->tickets);
            }

            return $existing->state === 'pending' ? SupplierResult::pending('Toujours en attente de traitement.') : SupplierResult::unknown('Operation precedente non confirmee : verification fournisseur requise.');
        }

        if ($existing) { // 'retry' ou 'failed' : nouvelle tentative controlee
            $existing->update(['state' => 'started', 'attempts' => $existing->attempts + 1, 'result' => null]);
        } else {
            FlightIdempotencyKey::create(['key' => $key, 'operation' => $operation, 'flight_booking_id' => $flight->id, 'state' => 'started']);
        }

        $result = $this->call($flight, $operation, $key, fn ($driver) => $fn($driver, $key));
        $this->markKey($flight, $operation, match ($result->status) {
            SupplierResult::SUCCESS => 'completed',
            SupplierResult::FAILED => 'failed',
            SupplierResult::PENDING => 'pending',
            default => 'unknown',
        }, $result);

        return $result;
    }

    protected function call(FlightBooking $flight, string $operation, ?string $key, callable $fn): SupplierResult
    {
        $started = microtime(true);
        try {
            $result = $fn($this->suppliers->driver($flight->supplier));
        } catch (Throwable $e) {
            report($e);
            $result = SupplierResult::unknown('Erreur de communication fournisseur : '.mb_substr($e->getMessage(), 0, 200));
        }

        FlightSupplierLog::create([
            'flight_booking_id' => $flight->id,
            'flight_supplier_id' => $flight->flight_supplier_id,
            'operation' => $operation,
            'idempotency_key' => $key,
            'success' => $result->isSuccess(),
            'outcome' => $result->status,
            'external_ref' => $result->pnr ?? $result->supplierReference,
            'request' => ['reference' => $flight->reference, 'passengers' => $flight->passenger_count],
            'response' => $result->toArray(),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        return $result;
    }

    protected function markKey(FlightBooking $flight, string $operation, string $state, SupplierResult $result): void
    {
        FlightIdempotencyKey::updateOrCreate(
            ['key' => "{$operation}:{$flight->id}"],
            ['operation' => $operation, 'flight_booking_id' => $flight->id, 'state' => $state, 'result' => $result->toArray()],
        );
    }

    protected function keyState(FlightBooking $flight, string $operation): ?string
    {
        return FlightIdempotencyKey::where('key', "{$operation}:{$flight->id}")->value('state');
    }

    protected function resultFromArray(array $a): SupplierResult
    {
        return new SupplierResult($a['status'], $a['pnr'] ?? null, $a['supplier_reference'] ?? null, $a['tickets'] ?? [], $a['message'] ?? null);
    }
}
