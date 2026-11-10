<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Flights\FlightAfterSale;
use App\Models\Flights\FlightBooking;
use App\Services\Flights\FlightException;
use App\Services\Flights\FlightFulfillmentService;
use App\Services\Flights\FlightStateMachine;
use App\Services\Flights\FlightStatus;
use App\Services\Flights\InvalidFlightTransition;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Back-office billetterie : suivi des dossiers, saisie PNR / billets,
 * incidents, apres-vente, remboursements et rapprochement.
 */
class FlightBookingController extends Controller
{
    public function __construct(protected FlightFulfillmentService $fulfillment) {}

    public function index(Request $request): Response
    {
        $query = FlightBooking::query()
            ->with('user:id,name,email,phone')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('attention'), fn ($q) => $q->where('needs_attention', true))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', '%'.$request->string('q').'%')
                ->orWhere('pnr', 'like', '%'.$request->string('q').'%')
                ->orWhere('contact_name', 'like', '%'.$request->string('q').'%')
                ->orWhere('contact_phone', 'like', '%'.$request->string('q').'%')));

        return Inertia::render('Admin/Vols/Index', [
            'dossiers' => $query->orderByDesc('needs_attention')->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status', 'attention', 'q'),
            'statuses' => FlightStatus::LABELS,
            'stats' => [
                'attention' => FlightBooking::where('needs_attention', true)->count(),
                'toBook' => FlightBooking::whereIn('status', [FlightStatus::PAYMENT_CONFIRMED, FlightStatus::BOOKING_PENDING])->count(),
                'toTicket' => FlightBooking::where('status', FlightStatus::TICKETING_PENDING)->count(),
                'ticketed' => FlightBooking::where('status', FlightStatus::TICKETED)->count(),
                'afterSales' => FlightAfterSale::whereIn('status', ['submitted', 'in_review'])->count(),
                'refunds' => FlightBooking::where('status', FlightStatus::REFUND_PENDING)->count(),
            ],
        ]);
    }

    public function show(Request $request, FlightBooking $flightBooking): Response
    {
        // Consultation des donnees passagers (passeports) : journalisee.
        AuditLog::record('flight.view', $flightBooking);

        $flightBooking->load([
            'user:id,name,email,phone', 'supplier:id,code,name,driver', 'passengers.ticket', 'afterSales.user:id,name',
            'histories.actor:id,name', 'supplierLogs', 'booking.payments',
        ]);

        $passengers = $flightBooking->passengers->map(fn ($p) => $p->toArray() + ['document_number' => $p->document_number]);
        $paid = (float) ($flightBooking->booking?->payments->where('status', 'paid')->sum('amount') ?? 0)
            + (float) ($flightBooking->booking?->payments->where('status', 'refunded')->sum('amount') ?? 0);

        return Inertia::render('Admin/Vols/Show', [
            'dossier' => $flightBooking,
            'passengers' => $passengers,
            'statuses' => FlightStatus::LABELS,
            'allowed' => FlightStatus::TRANSITIONS[$flightBooking->status] ?? [],
            // Offre Aviasales (prix indicatif) : liens pour acheter le billet.
            'aviasales' => ($tp = $flightBooking->offer?->raw['travelpayouts'] ?? null)
                ? ['links' => $tp['links'] ?? [], 'price_eur' => $tp['price_eur'] ?? null] : null,
            'afterSaleStatuses' => FlightAfterSale::STATUSES,
            // Rapprochement : encaisse / cout fournisseur / marge / frais / rembourse.
            'reconciliation' => [
                'collected' => $paid,
                'supplier_cost' => (float) $flightBooking->supplier_cost,
                'markup' => (float) $flightBooking->markup_amount,
                'service_fee' => (float) $flightBooking->service_fee,
                'commission' => (float) $flightBooking->commission_amount,
                'refunded' => (float) $flightBooking->refunded_amount,
                'net' => $paid > 0 ? $paid - (float) $flightBooking->supplier_cost - (float) $flightBooking->commission_amount - (float) $flightBooking->refunded_amount : 0,
            ],
        ]);
    }

    public function process(FlightBooking $flightBooking): RedirectResponse
    {
        return $this->act($flightBooking, 'flight.process', fn () => $this->fulfillment->process($flightBooking), 'Traitement relance.');
    }

    public function reconcile(FlightBooking $flightBooking): RedirectResponse
    {
        return $this->act($flightBooking, 'flight.reconcile', fn () => $this->fulfillment->reconcile($flightBooking), 'Etat verifie aupres du fournisseur.');
    }

    public function recordPnr(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $data = $request->validate([
            'pnr' => ['required', 'string', 'between:5,8', 'alpha_num'],
            'supplier_booking_ref' => ['nullable', 'string', 'max:60'],
            'ticketing_deadline' => ['nullable', 'date'],
        ]);

        return $this->act($flightBooking, 'flight.pnr', function () use ($flightBooking, $data) {
            if (! in_array($flightBooking->status, [FlightStatus::BOOKING_PENDING, FlightStatus::FAILED], true)) {
                throw new FlightException('Le PNR se saisit sur un dossier paye en attente de reservation.');
            }
            $this->fulfillment->recordBooked($flightBooking, $data['pnr'], $data['supplier_booking_ref'] ?? null, 'PNR saisi par l\'equipe billetterie', $data['ticketing_deadline'] ?? null);
            $this->fulfillment->process($flightBooking->fresh());
        }, 'PNR enregistre : reservation confirmee.');
    }

    public function recordTickets(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $data = $request->validate([
            'tickets' => ['required', 'array'],
            'tickets.*' => ['required', 'string', 'regex:/^[0-9\- ]{10,20}$/'],
        ], ['tickets.*.regex' => 'Numero de billet : 13 chiffres (ex. 0711234567890).']);

        return $this->act($flightBooking, 'flight.tickets', fn () => $this->fulfillment->recordTickets($flightBooking, $data['tickets']), 'Billets enregistres : dossier TICKETED, le client est notifie.');
    }

    public function markFailed(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:250']]);

        return $this->act($flightBooking, 'flight.fail', fn () => $this->fulfillment->markFailed($flightBooking, $data['reason']), 'Dossier passe en echec.');
    }

    public function cancel(Request $request, FlightBooking $flightBooking, FlightStateMachine $machine): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:250']]);

        return $this->act($flightBooking, 'flight.cancel', function () use ($flightBooking, $data, $machine) {
            $machine->transition($flightBooking, FlightStatus::CANCELLED, ['cancelled_at' => now()], $data['reason']);
            if (! $flightBooking->isPaid()) {
                $flightBooking->booking?->update(['status' => 'cancelled']);
            }
            app(\App\Services\Flights\FlightNotifier::class)->statusChanged($flightBooking->fresh());
        }, $flightBooking->isPaid() ? 'Dossier annule : lancez le remboursement.' : 'Dossier annule.');
    }

    public function startRefund(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:250'],
        ]);

        return $this->act($flightBooking, 'flight.refund_start', fn () => $this->fulfillment->startRefund($flightBooking, (float) $data['amount'], $data['reason']), 'Remboursement lance.');
    }

    public function confirmRefund(Request $request, FlightBooking $flightBooking): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403, 'Confirmation des remboursements reservee aux administrateurs.');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$flightBooking->total_amount],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:250'],
        ]);

        return $this->act($flightBooking, 'flight.refund_done', fn () => $this->fulfillment->confirmRefund($flightBooking, (float) $data['amount'], $data['reference'] ?? null, $data['note'] ?? null), 'Remboursement confirme et rapproche.');
    }

    public function resolveAttention(FlightBooking $flightBooking): RedirectResponse
    {
        $flightBooking->update(['needs_attention' => false, 'attention_reason' => null]);
        AuditLog::record('flight.resolve_attention', $flightBooking);

        return back()->with('success', 'Alerte levee.');
    }

    public function updateAfterSale(Request $request, FlightAfterSale $afterSale): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(FlightAfterSale::STATUSES))],
            'supplier_fee' => ['nullable', 'numeric', 'min:0'],
            'psp_fee' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
            'refundable_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_delay' => ['nullable', 'string', 'max:60'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $afterSale->update($data + ['processed_by' => $request->user()->id, 'processed_at' => now()]);
        $afterSale->booking->update(['after_sales_status' => $data['status']]);
        AuditLog::record('flight.after_sale', $afterSale->booking, ['after_sale' => $afterSale->id, 'status' => $data['status']]);

        return back()->with('success', 'Demande apres-vente mise a jour.');
    }

    public function attachment(FlightAfterSale $afterSale, int $index): StreamedResponse
    {
        $path = $afterSale->attachments[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        AuditLog::record('flight.attachment', $afterSale->booking, ['file' => basename($path)]);

        return Storage::disk('local')->download($path);
    }

    protected function act(FlightBooking $flight, string $action, Closure $fn, string $success): RedirectResponse
    {
        try {
            $fn();
        } catch (FlightException|InvalidFlightTransition $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLog::record($action, $flight, ['status' => $flight->fresh()->status]);

        return back()->with('success', $success);
    }
}
