<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * DUFFEL (api.duffel.com) : vols en direct de centaines de compagnies (NDC +
 * GDS), inscription libre et gratuite, recherche gratuite (jusqu'a 1500
 * recherches par reservation), paiement a la reservation seulement.
 *
 *  - Recherche : offer_requests -> une offre par resultat, vrais troncons.
 *  - Revalidation : GET /air/offers/{id} (prix et disponibilite a jour).
 *  - Reservation + billet :
 *      DUFFEL_AUTO_TICKETING=false (defaut) : l'equipe reserve dans le
 *      tableau de bord Duffel puis saisit PNR et billets dans le dossier ;
 *      DUFFEL_AUTO_TICKETING=true : commande creee automatiquement apres le
 *      paiement Mobile Money, payee sur le solde Duffel (billets emis).
 *
 * Prix convertis en XAF : EUR au taux fixe (1 EUR = 655,957 XAF) ; pour un
 * compte Duffel en USD / GBP, renseigner FX_USD_XAF / FX_GBP_XAF.
 */
class DuffelSupplier extends AbstractSupplier
{
    protected const CABINS = ['economy' => 'economy', 'premium' => 'premium_economy', 'business' => 'business', 'first' => 'first'];

    public static function configured(): bool
    {
        return ! empty(config('flights.duffel.token'));
    }

    public function search(SearchCriteria $c): array
    {
        $passengers = array_merge(
            array_fill(0, $c->adults, ['type' => 'adult']),
            array_fill(0, $c->children, ['age' => 8]),
            array_fill(0, $c->infants, ['type' => 'infant_without_seat']),
        );

        $res = $this->http()->post('/air/offer_requests?return_offers=true&supplier_timeout='.(int) config('flights.duffel.supplier_timeout_ms', 20000), [
            'data' => [
                'slices' => array_map(fn ($l) => [
                    'origin' => $l['origin'],
                    'destination' => $l['destination'],
                    'departure_date' => $l['date']->toDateString(),
                ], $c->itinerary()),
                'passengers' => $passengers,
                'cabin_class' => self::CABINS[$c->cabin] ?? 'economy',
            ],
        ]);
        $this->assertOk($res);

        $offers = collect($res->json('data.offers') ?? [])
            ->sortBy(fn ($o) => (float) $o['total_amount'])
            ->take((int) config('flights.duffel.max_offers', 50));

        return $offers->map(fn ($o) => $this->toOffer($o, $c))->filter()->values()->all();
    }

    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        $id = $offer->raw['duffel']['offer_id'] ?? null;
        if (! $id) {
            return null;
        }
        $res = $this->http()->get('/air/offers/'.$id, ['return_available_services' => 'false']);
        if (in_array($res->status(), [404, 410, 422], true)) {
            return null; // offre expiree ou plus vendable
        }
        $this->assertOk($res);
        $o = $res->json('data');
        if (! empty($o['expires_at']) && CarbonImmutable::parse($o['expires_at'])->isPast()) {
            return null;
        }

        return $this->toOffer($o, $c);
    }

    public function book(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        if (! config('flights.duffel.auto_ticketing')) {
            return SupplierResult::pending("Reservation a faire par l'equipe billetterie dans le tableau de bord Duffel.");
        }

        $duffel = $booking->offer?->raw['duffel'] ?? null;
        if (! $duffel) {
            return SupplierResult::failed('Offre Duffel introuvable pour ce dossier.');
        }

        $order = [
            'type' => 'instant',
            'selected_offers' => [$duffel['offer_id']],
            'passengers' => $this->orderPassengers($booking, $duffel['passenger_ids'] ?? []),
            'payments' => [['type' => 'balance', 'currency' => $duffel['currency'], 'amount' => $duffel['amount']]],
            'metadata' => ['mokili_reference' => (string) $booking->reference],
        ];

        try {
            $res = $this->http()->withHeaders(['Idempotency-Key' => $idempotencyKey])->timeout(130)->post('/air/orders', ['data' => $order]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return SupplierResult::unknown('Duffel ne repond pas : '.$e->getMessage());
        }
        if (! $res->successful()) {
            $msg = $res->json('errors.0.message') ?? ('HTTP '.$res->status());

            return $res->status() >= 500 ? SupplierResult::unknown('Duffel : '.$msg) : SupplierResult::failed('Duffel : '.$msg);
        }

        return SupplierResult::success($res->json('data.booking_reference'), $res->json('data.id'), $this->ticketsFrom($booking, $res->json('data') ?? []));
    }

    public function issue(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        if (! config('flights.duffel.auto_ticketing') || ! $booking->supplier_booking_ref) {
            return SupplierResult::pending("Emission a realiser par l'equipe billetterie dans Duffel.");
        }
        $status = $this->retrieve($booking);

        return $status->tickets
            ? SupplierResult::success($status->pnr, $status->supplierReference, $status->tickets)
            : SupplierResult::pending('Billets en cours d\'emission chez la compagnie.');
    }

    public function retrieve(FlightBooking $booking): SupplierResult
    {
        if ($booking->supplier_booking_ref && str_starts_with((string) $booking->supplier_booking_ref, 'ord_')) {
            $res = $this->http()->get('/air/orders/'.$booking->supplier_booking_ref);
            if ($res->successful()) {
                $tickets = $this->ticketsFrom($booking, $res->json('data') ?? []);

                return new SupplierResult(SupplierResult::SUCCESS, $res->json('data.booking_reference'), $res->json('data.id'), $tickets,
                    null, [], 'booked', $tickets ? 'issued' : 'pending');
            }
        }
        $tickets = $booking->tickets()->pluck('ticket_number', 'flight_passenger_id')->all();

        return new SupplierResult($booking->pnr ? SupplierResult::SUCCESS : SupplierResult::PENDING, $booking->pnr, $booking->supplier_booking_ref,
            $tickets, null, [], $booking->pnr ? 'booked' : 'pending', $tickets ? 'issued' : ($booking->pnr ? 'pending' : 'none'));
    }

    public function cancel(FlightBooking $booking): SupplierResult
    {
        return SupplierResult::pending("Annulation a realiser par l'equipe billetterie dans Duffel.");
    }

    // ------------------------------------------------------------ internes

    protected function http()
    {
        if (! self::configured()) {
            throw new RuntimeException('Duffel : jeton DUFFEL_ACCESS_TOKEN non configure.');
        }

        return Http::baseUrl(config('flights.duffel.endpoint', 'https://api.duffel.com'))
            ->withToken(config('flights.duffel.token'))
            ->withHeaders(['Duffel-Version' => 'v2', 'Accept' => 'application/json'])
            ->acceptJson()
            ->timeout((int) config('flights.duffel.timeout', 40));
    }

    protected function assertOk(Response $res): void
    {
        if (! $res->successful()) {
            throw new RuntimeException('Duffel : '.($res->json('errors.0.message') ?? 'HTTP '.$res->status()));
        }
    }

    /** Taux de conversion vers le XAF ; null = devise non geree (offre ignoree). */
    protected function rate(string $currency): ?float
    {
        $rate = config('flights.duffel.fx.'.strtoupper($currency));

        return $rate ? (float) $rate : null;
    }

    /** "PT11H30M" / "P1DT2H" -> minutes */
    protected function minutes(?string $iso): int
    {
        if (! $iso || ! preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?)?/', $iso, $m)) {
            return 0;
        }

        return ((int) ($m[1] ?? 0)) * 1440 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }

    protected function toOffer(array $o, SearchCriteria $c): ?OfferData
    {
        $currency = strtoupper((string) ($o['total_currency'] ?? ''));
        $rate = $this->rate($currency);
        if (! $rate || empty($o['slices'])) {
            return null;
        }
        $toXaf = fn ($v) => round((float) $v * $rate);

        $checked = null;
        $carryOn = null;
        $slices = [];
        foreach ($o['slices'] as $s) {
            $segs = $s['segments'] ?? [];
            if (! $segs) {
                return null;
            }
            foreach ($segs[0]['passengers'] ?? [] as $p) {
                foreach ($p['baggages'] ?? [] as $b) {
                    if ($b['type'] === 'checked') {
                        $checked ??= (int) $b['quantity'];
                    } elseif ($b['type'] === 'carry_on') {
                        $carryOn ??= (int) $b['quantity'];
                    }
                }
            }
            $first = $segs[0];
            $last = $segs[count($segs) - 1];
            $slices[] = [
                'origin' => $first['origin']['iata_code'],
                'destination' => $last['destination']['iata_code'],
                'departure_at' => substr($first['departing_at'], 0, 16),
                'arrival_at' => substr($last['arriving_at'], 0, 16),
                'duration_minutes' => $this->minutes($s['duration'] ?? null),
                'stops' => count($segs) - 1,
                'via' => count($segs) > 1 ? $first['destination']['iata_code'] : null,
                'stop_airports' => array_map(fn ($g) => $g['destination']['iata_code'], array_slice($segs, 0, -1)),
                'segments' => array_map(fn ($g) => [
                    'airline_code' => $g['marketing_carrier']['iata_code'] ?? null,
                    'airline_name' => $g['marketing_carrier']['name'] ?? null,
                    'flight_number' => trim(($g['marketing_carrier']['iata_code'] ?? '').' '.($g['marketing_carrier_flight_number'] ?? '')),
                    'origin' => $g['origin']['iata_code'],
                    'destination' => $g['destination']['iata_code'],
                    'departure_at' => substr($g['departing_at'], 0, 16),
                    'arrival_at' => substr($g['arriving_at'], 0, 16),
                    'duration_minutes' => $this->minutes($g['duration'] ?? null) ?: null,
                    'aircraft' => $g['aircraft']['name'] ?? null,
                    'operated_by' => ($g['operating_carrier']['iata_code'] ?? null) !== ($g['marketing_carrier']['iata_code'] ?? null)
                        ? ($g['operating_carrier']['name'] ?? null) : null,
                    'cabin' => $c->cabin,
                ], $segs),
            ];
        }

        // Prix par type de passager (le detail Duffel est global) : reparti
        // entre les places, un bebe comptant pour 10 % d'une place.
        $total = $toXaf($o['total_amount']);
        $seats = max(1, $c->adults + $c->children);
        $seatUnit = round($total / ($seats + 0.1 * $c->infants), 2);
        $infantUnit = round($seatUnit * 0.1, 2);
        $lines = $this->passengerLines($c, $seatUnit, $seatUnit, $infantUnit);
        $diff = round($total - $this->linesTotal($lines), 2); // arrondis : reportes sur l'adulte
        if ($diff != 0.0) {
            $lines[0]['unit_amount'] = round($lines[0]['unit_amount'] + $diff / max(1, $lines[0]['count']), 2);
        }

        $refund = $o['conditions']['refund_before_departure'] ?? null;
        $change = $o['conditions']['change_before_departure'] ?? null;
        $owner = $o['owner']['iata_code'] ?? ($slices[0]['segments'][0]['airline_code'] ?? null);

        return new OfferData(
            supplierOfferId: 'duffel:'.$o['id'],
            validatingAirline: $owner,
            airlineName: $o['owner']['name'] ?? $owner,
            slices: $slices,
            passengers: $lines,
            supplierAmount: $total,
            currency: 'XAF',
            fareRules: [
                'cabin' => $c->cabin,
                'baggage' => $checked ? $checked.' bagage'.($checked > 1 ? 's' : '').' en soute' : null,
                'cabin_baggage' => $carryOn ? $carryOn.' bagage cabine' : null,
                'refundable' => (bool) ($refund['allowed'] ?? false),
                'changeable' => (bool) ($change['allowed'] ?? false),
                'change_fee' => isset($change['penalty_amount']) && $this->rate((string) ($change['penalty_currency'] ?? $currency)) ? round((float) $change['penalty_amount'] * $this->rate((string) ($change['penalty_currency'] ?? $currency))) : null,
                'cancel_fee' => isset($refund['penalty_amount']) && $this->rate((string) ($refund['penalty_currency'] ?? $currency)) ? round((float) $refund['penalty_amount'] * $this->rate((string) ($refund['penalty_currency'] ?? $currency))) : null,
                'conditions' => 'Tarif '.($o['owner']['name'] ?? '').' via Duffel. Prix fournisseur : '.$o['total_amount'].' '.$currency.'.',
            ],
            ticketingDeadline: $o['payment_requirements']['payment_required_by'] ?? null,
            raw: ['duffel' => [
                'offer_id' => $o['id'],
                'amount' => (string) $o['total_amount'],
                'currency' => $currency,
                'passenger_ids' => array_map(fn ($p) => ['id' => $p['id'], 'type' => $p['type'] ?? (isset($p['age']) ? 'child' : 'adult')], $o['passengers'] ?? []),
                'expires_at' => $o['expires_at'] ?? null,
            ]],
        );
    }

    /** Passagers du dossier -> passagers Duffel (memes ids que l'offre, dans l'ordre adulte / enfant / bebe). */
    protected function orderPassengers(FlightBooking $booking, array $offerPassengers): array
    {
        $order = ['ADT' => 0, 'CHD' => 1, 'INF' => 2];
        $ours = $booking->passengers()->get()->sortBy(fn ($p) => $order[$p->type] ?? 9)->values();
        $byType = ['adult' => [], 'child' => [], 'infant_without_seat' => []];
        foreach ($offerPassengers as $p) {
            $type = in_array($p['type'], ['adult', 'infant_without_seat'], true) ? $p['type'] : 'child';
            $byType[$type][] = $p['id'];
        }
        $phone = '+'.ltrim(preg_replace('/[^\d+]/', '', (string) $booking->contact_phone), '+');

        $out = [];
        foreach ($ours as $p) {
            $type = match ($p->type) { 'INF' => 'infant_without_seat', 'CHD' => 'child', default => 'adult' };
            $id = array_shift($byType[$type]);
            if (! $id) {
                continue;
            }
            $gender = strtolower((string) ($p->gender ?: (in_array($p->title, ['Mme', 'Mlle'], true) ? 'f' : 'm')));
            $row = [
                'id' => $id,
                'title' => match ($p->title) { 'Mme' => 'mrs', 'Mlle' => 'miss', default => $gender === 'f' ? 'ms' : 'mr' },
                'gender' => $gender === 'f' ? 'f' : 'm',
                'given_name' => $p->first_name,
                'family_name' => $p->last_name,
                'born_on' => $p->birth_date?->toDateString(),
                'email' => $p->email ?: $booking->contact_email,
                'phone_number' => $p->phone ?: $phone,
            ];
            if ($p->document_number && $p->document_expiry) {
                $row['identity_documents'] = [[
                    'type' => 'passport',
                    'unique_identifier' => $p->document_number,
                    'issuing_country_code' => $p->document_country ?: ($p->nationality ?: 'CG'),
                    'expires_on' => \Carbon\Carbon::parse($p->document_expiry)->toDateString(),
                ]];
            }
            $out[] = ['row' => $row, 'type' => $type];
        }

        // Chaque bebe voyage sur les genoux d'un adulte.
        $adults = array_keys(array_filter($out, fn ($x) => $x['type'] === 'adult'));
        foreach (array_filter($out, fn ($x) => $x['type'] === 'infant_without_seat') as $x) {
            $i = array_shift($adults);
            if ($i !== null) {
                $out[$i]['row']['infant_passenger_id'] = $x['row']['id'];
            }
        }

        return array_map(fn ($x) => $x['row'], $out);
    }

    /** Billets electroniques de la commande -> [flight_passenger_id => numero]. */
    protected function ticketsFrom(FlightBooking $booking, array $order): array
    {
        $docs = array_values(array_filter($order['documents'] ?? [], fn ($d) => ($d['type'] ?? '') === 'electronic_ticket'));
        if (! $docs) {
            return [];
        }
        $order_ = ['ADT' => 0, 'CHD' => 1, 'INF' => 2];
        $ours = $booking->passengers()->get()->sortBy(fn ($p) => $order_[$p->type] ?? 9)->values();
        $duffelIds = array_column($order['passengers'] ?? [], 'id');
        $tickets = [];
        foreach ($docs as $d) {
            foreach ($d['passenger_ids'] ?? [] as $pid) {
                $idx = array_search($pid, $duffelIds, true);
                if ($idx !== false && isset($ours[$idx])) {
                    $tickets[$ours[$idx]->id] = $d['unique_identifier'];
                }
            }
        }

        return count($tickets) === $ours->count() ? $tickets : [];
    }
}
