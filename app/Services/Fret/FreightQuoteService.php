<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightOffer;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\Fret\Quote;
use App\Models\Fret\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FreightQuoteService
{
    public function __construct(private FreightNotifier $notifier, private FreightOrderService $orders) {}

    public function createRequest(User $user, array $data, ?FreightOffer $offer = null): QuoteRequest
    {
        if ($offer && (! $offer->is_active || $offer->status !== 'published')) {
            throw new RuntimeException('Cette prestation n\'est plus disponible.');
        }

        $request = QuoteRequest::create($data + [
            'user_id' => $user->id,
            'freight_offer_id' => $offer?->id,
            'partner_user_id' => $offer?->carrier_id,
            'status' => 'DEMANDE_ENVOYEE',
        ]);

        FreightAudit::log('quote_request.created', $request, 'user:'.$user->id);
        $this->notifier->notify($user, 'request_sent', $request->reference, "Votre demande {$request->reference} a ete transmise.");
        if (! $offer) {
            // Demande generale : les partenaires approuves du mode concerne sont prevenus.
            FreightPartnerProfile::where('status', 'approved')->whereJsonContains('modes', $request->mode)->with('user')->get()
                ->each(fn ($p) => $this->notifier->notify($p->user, 'request_received', $request->reference, "Nouvelle demande de devis {$request->reference} ({$request->origin_city} -> {$request->destination_city})."));
        }
        $this->notifier->notify($offer?->carrier, 'request_received', $request->reference, "Nouvelle demande de devis {$request->reference} ({$request->origin_city} -> {$request->destination_city}).");

        return $request;
    }

    /** Un partenaire accuse reception ou repond par un devis ; une nouvelle version remplace la precedente. */
    public function respond(User $partner, QuoteRequest $request, array $data): Quote
    {
        if ($request->partner_user_id && $request->partner_user_id !== $partner->id) {
            throw new RuntimeException('Demande reservee a un autre partenaire.');
        }
        if (in_array($request->status, ['DEVIS_ACCEPTE', 'ANNULE'], true)) {
            throw new RuntimeException('Cette demande est deja cloturee.');
        }

        $lines = array_values($data['lines']);
        $total = collect($lines)->where('kind', '!=', 'excluded')->sum(fn ($l) => (float) $l['amount']);
        if ($total <= 0) {
            throw new RuntimeException('Le total du devis doit etre superieur a zero.');
        }

        return DB::transaction(function () use ($partner, $request, $data, $lines, $total) {
            $version = (int) Quote::where('quote_request_id', $request->id)->where('partner_user_id', $partner->id)->max('version') + 1;

            Quote::where('quote_request_id', $request->id)->where('partner_user_id', $partner->id)->where('status', 'sent')
                ->update(['status' => 'superseded']);

            $quote = Quote::create([
                'quote_request_id' => $request->id,
                'partner_user_id' => $partner->id,
                'version' => $version,
                'currency' => $data['currency'] ?? 'XAF',
                'lines' => $lines,
                'total_amount' => round($total, 2),
                'is_firm' => (bool) ($data['is_firm'] ?? true),
                'valid_until' => $data['valid_until'],
                'transit_days' => $data['transit_days'] ?? null,
                'conditions' => $data['conditions'] ?? null,
                'exclusions' => $data['exclusions'] ?? null,
            ]);

            // Demande generale : elle reste visible des autres partenaires (comparaison) ; seul le premier accuse reception est horodate.
            $request->update(['status' => 'DEVIS_TRANSMIS', 'partner_ack_at' => $request->partner_ack_at ?? now()]);

            FreightAudit::log('quote.sent', $quote, 'user:'.$partner->id, null, ['version' => $version, 'total' => $quote->total_amount]);
            $this->notifier->notify($request->user, 'quote_available', $quote->reference, "Un devis est disponible pour votre demande {$request->reference}.");

            return $quote;
        });
    }

    /** Acceptation explicite : revalide la validite, fige les conditions et cree la commande. */
    public function accept(User $user, Quote $quote, bool $termsAccepted)
    {
        $request = $quote->request;
        abort_unless($request->user_id === $user->id, 403);

        if (! $termsAccepted) {
            throw new RuntimeException('Vous devez accepter explicitement le devis et ses conditions.');
        }
        if ($quote->status !== 'sent') {
            throw new RuntimeException('Ce devis n\'est plus valable (remplace, refuse ou deja accepte).');
        }
        if ($quote->isExpired()) {
            $quote->update(['status' => 'expired']);
            throw new RuntimeException('Ce devis a expire. Demandez une nouvelle proposition au partenaire.');
        }

        return $this->orders->createFromQuote($user, $quote);
    }

    public function refuse(User $user, Quote $quote, ?string $reason = null): Quote
    {
        abort_unless($quote->request->user_id === $user->id, 403);
        if ($quote->status !== 'sent') {
            throw new RuntimeException('Ce devis ne peut plus etre refuse.');
        }

        $quote->update(['status' => 'refused']);
        FreightAudit::log('quote.refused', $quote, 'user:'.$user->id, $reason);
        $this->notifier->notify($quote->partner, 'quote_refused', $quote->reference, "Le devis {$quote->reference} a ete refuse par le client.");

        return $quote;
    }
}
