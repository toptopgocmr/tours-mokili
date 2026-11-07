<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOperation;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightSearch;
use Carbon\CarbonInterval;
use Throwable;

/**
 * Connecteur Amadeus Self-Service (Flight Offers Search, Flight Offers Price,
 * Flight Create Orders / Order Management).
 *
 * IMPORTANT - emission : l'API Self-Service cree le PNR mais n'emet pas de
 * billet par elle-meme ; l'emission passe par un consolidateur (contrat
 * Amadeus) ou l'API Enterprise. issueTickets() relit donc l'ordre : si les
 * billets existent ils sont rattaches, sinon le dossier reste en attente
 * d'emission (anomalie visible, jamais de billet annonce a tort) et la
 * commande `flights:sync` les rattachera des qu'ils apparaissent.
 */
class AmadeusFlightSupplier implements FlightSupplierDriver, SyncsBooking
{
    private const CABINS = ['economy' => 'ECONOMY', 'premium' => 'PREMIUM_ECONOMY', 'business' => 'BUSINESS'];

    public function __construct(private AmadeusClient $client) {}

    public function search(FlightSearch $search): array
    {
        if (! empty($search->legs)) {
            $origins = [];
            foreach ($search->legs as $n => $leg) {
                $origins[] = [
                    'id' => (string) ($n + 1), 'originLocationCode' => $leg['origin'], 'destinationLocationCode' => $leg['destination'],
                    'departureDateTimeRange' => ['date' => $leg['date']],
                ];
            }
        } else {
            $origins = [[
                'id' => '1', 'originLocationCode' => $search->origin, 'destinationLocationCode' => $search->destination,
                'departureDateTimeRange' => ['date' => $search->depart_date->format('Y-m-d')],
            ]];
        }
        if (empty($search->legs) && $search->return_date) {
            $origins[] = [
                'id' => '2', 'originLocationCode' => $search->destination, 'destinationLocationCode' => $search->origin,
                'departureDateTimeRange' => ['date' => $search->return_date->format('Y-m-d')],
            ];
        }

        $res = $this->client->request('POST', 'v2/shopping/flight-offers', ['json' => [
            'currencyCode' => config('flights.amadeus.currency', 'EUR'),
            'originDestinations' => $origins,
            'travelers' => $this->searchTravelers($search),
            'sources' => ['GDS'],
            'searchCriteria' => [
                'maxFlightOffers' => (int) config('flights.amadeus.max_offers', 20),
                'flightFilters' => array_filter([
                    'cabinRestrictions' => [[
                        'cabin' => self::CABINS[$search->cabin] ?? 'ECONOMY',
                        'coverage' => 'MOST_SEGMENTS',
                        'originDestinationIds' => array_column($origins, 'id'),
                    ]],
                    'connectionRestriction' => $search->direct_only ? ['maxNumberOfConnections' => 0] : null,
                ]),
            ],
        ]]);

        $carriers = $res['dictionaries']['carriers'] ?? [];
        $offers = [];

        $currency = config('flights.amadeus.currency', 'XAF');

        foreach ($res['data'] ?? [] as $o) {
            // Tout est affiche et paye dans une seule devise : on ecarte toute offre non convertie.
            if (($o['price']['currency'] ?? null) !== $currency) {
                continue;
            }

            $code = $o['validatingAirlineCodes'][0] ?? ($o['itineraries'][0]['segments'][0]['carrierCode'] ?? null);

            $offers[] = [
                'external_id' => (string) $o['id'],
                'itinerary' => array_filter([
                    'outbound' => $this->segments($o['itineraries'][0]['segments'] ?? []),
                    'inbound' => empty($search->legs) ? $this->segments($o['itineraries'][1]['segments'] ?? []) : [],
                    'legs' => empty($search->legs) ? null : array_map(fn ($it) => $this->segments($it['segments'] ?? []), $o['itineraries'] ?? []),
                ], fn ($v) => $v !== null),
                'airline_code' => $code,
                'airline_name' => $carriers[$code] ?? $code,
                'cost' => (float) $o['price']['grandTotal'],
                'currency' => $o['price']['currency'],
                'fare_rules' => $this->fareRules($o),
                'expires_at' => now()->addMinutes((int) config('flights.offer_ttl_minutes', 20)),
                'raw' => $o,
            ];
        }

        return $offers;
    }

    public function revalidate(FlightOffer $offer): array
    {
        if (! $offer->raw) {
            return ['available' => false, 'cost' => 0.0, 'currency' => $offer->currency];
        }

        try {
            $res = $this->client->request('POST', 'v1/shopping/flight-offers/pricing', ['json' => [
                'data' => ['type' => 'flight-offers-pricing', 'flightOffers' => [$offer->raw]],
            ]]);
        } catch (SupplierRejectedException $e) {
            return ['available' => false, 'cost' => 0.0, 'currency' => $offer->currency]; // plus de place / tarif retire
        }

        $priced = $res['data']['flightOffers'][0] ?? null;
        if (! $priced || ($priced['price']['currency'] ?? null) !== config('flights.amadeus.currency', 'XAF')) {
            return ['available' => false, 'cost' => 0.0, 'currency' => $offer->currency];
        }

        $offer->update(['raw' => $priced]); // offre re-pricee, utilisee pour la commande

        return ['available' => true, 'cost' => (float) $priced['price']['grandTotal'], 'currency' => $priced['price']['currency']];
    }

    public function createBooking(FlightOrder $order, string $idempotencyKey): array
    {
        if ($existing = $this->findBooking($idempotencyKey)) {
            return $existing; // meme cle = meme reservation
        }

        $offer = $order->offer;
        if (! $offer?->raw) {
            throw new SupplierRejectedException('Offre fournisseur introuvable : relancez la recherche.');
        }

        $ticketing = ['option' => config('flights.amadeus.ticketing_option', 'DELAY_TO_CANCEL')];
        if ($ticketing['option'] === 'DELAY_TO_CANCEL') {
            $ticketing['delay'] = config('flights.amadeus.ticketing_delay', '6D');
        }

        $res = $this->client->request('POST', 'v1/booking/flight-orders', ['json' => ['data' => [
            'type' => 'flight-order',
            'flightOffers' => [$offer->raw],
            'travelers' => $this->orderTravelers($order),
            'remarks' => ['general' => [['subType' => 'GENERAL_MISCELLANEOUS', 'text' => 'MOKILI '.$order->reference]]],
            'ticketingAgreement' => $ticketing,
        ]]]);

        $id = $res['data']['id'] ?? null;
        $pnr = $res['data']['associatedRecords'][0]['reference'] ?? null;
        if (! $id || ! $pnr) {
            throw new SupplierTimeoutException('Reponse Amadeus incomplete a la reservation : etat a verifier.');
        }

        // Lien cle d'idempotence -> reservation, ecrit AVANT tout autre traitement
        // (Amadeus n'offre pas de recherche par cle).
        FlightOperation::where('idempotency_key', $idempotencyKey)
            ->update(['external_id' => $pnr, 'response' => ['pnr' => $pnr, 'supplier_order_id' => $id]]);

        return ['pnr' => $pnr, 'supplier_order_id' => $id];
    }

    public function findBooking(string $idempotencyKey): ?array
    {
        $op = FlightOperation::where('idempotency_key', $idempotencyKey)->first();
        $id = $op?->response['supplier_order_id'] ?? null;
        if (! $id) {
            return null;
        }

        $local = ['pnr' => $op->response['pnr'] ?? $op->external_id, 'supplier_order_id' => $id];

        try {
            $res = $this->client->request('GET', 'v1/booking/flight-orders/'.rawurlencode($id));

            return ['pnr' => $res['data']['associatedRecords'][0]['reference'] ?? $local['pnr'], 'supplier_order_id' => $id];
        } catch (SupplierRejectedException $e) {
            return $e->getCode() === 404 ? null : $local;
        } catch (Throwable $e) {
            return $local; // on sait qu'elle a ete creee ; la relecture est temporairement impossible
        }
    }

    public function issueTickets(FlightOrder $order, string $idempotencyKey): array
    {
        $tickets = $this->ticketsFor($order);
        if ($tickets) {
            return $tickets;
        }

        throw new SupplierTimeoutException('Emission en attente cote consolidateur Amadeus : aucun billet encore disponible.');
    }

    public function findTickets(string $idempotencyKey): ?array
    {
        $op = FlightOperation::where('idempotency_key', $idempotencyKey)->first();
        $order = $op ? FlightOrder::with('passengers')->find($op->flight_order_id) : null;
        if (! $order || ! $order->supplier_order_id) {
            return null;
        }

        try {
            return $this->ticketsFor($order) ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function cancelBooking(FlightOrder $order): bool
    {
        if (! $order->supplier_order_id) {
            return true;
        }

        try {
            $this->client->request('DELETE', 'v1/booking/flight-orders/'.rawurlencode($order->supplier_order_id));

            return true;
        } catch (SupplierRejectedException $e) {
            return $e->getCode() === 404; // deja inexistant
        } catch (Throwable $e) {
            return false;
        }
    }

    public function remoteState(FlightOrder $order): ?array
    {
        if (! $order->supplier_order_id) {
            return null;
        }

        try {
            $res = $this->client->request('GET', 'v1/booking/flight-orders/'.rawurlencode($order->supplier_order_id));
        } catch (SupplierRejectedException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }

        return ['tickets' => $this->mapTickets($res, $order)];
    }

    // ------------------------------------------------------------------

    /** @return array<int,string> numeros de billet par flight_passenger_id (vide si incomplet) */
    private function ticketsFor(FlightOrder $order): array
    {
        $state = $this->remoteState($order->loadMissing('passengers'));
        $tickets = $state['tickets'] ?? [];

        return count($tickets) === $order->passengers->count() ? $tickets : [];
    }

    private function mapTickets(array $res, FlightOrder $order): array
    {
        $byTraveler = [];
        foreach ($res['data']['tickets'] ?? [] as $t) {
            if (! empty($t['documentNumber']) && ! in_array($t['documentStatus'] ?? 'ISSUED', ['VOID', 'REFUNDED'], true)) {
                foreach ((array) ($t['travelerIds'] ?? [$t['travelerId'] ?? null]) as $tid) {
                    if ($tid) {
                        $byTraveler[(string) $tid] = $t['documentNumber'];
                    }
                }
            }
        }

        $map = [];
        foreach ($this->orderedPassengers($order) as $i => $p) {
            if (isset($byTraveler[(string) ($i + 1)])) {
                $map[$p->id] = $byTraveler[(string) ($i + 1)];
            }
        }

        return $map;
    }

    /** Adultes, puis enfants, puis bebes : meme numerotation que la recherche (travelerPricings). */
    private function orderedPassengers(FlightOrder $order): array
    {
        $rank = ['adult' => 0, 'child' => 1, 'infant' => 2];

        return $order->passengers->sortBy(fn ($p) => [$rank[$p->type] ?? 0, $p->id])->values()->all();
    }

    private function searchTravelers(FlightSearch $s): array
    {
        $travelers = [];
        $id = 1;
        for ($i = 0; $i < $s->adults; $i++) {
            $travelers[] = ['id' => (string) $id++, 'travelerType' => 'ADULT'];
        }
        for ($i = 0; $i < $s->children; $i++) {
            $travelers[] = ['id' => (string) $id++, 'travelerType' => 'CHILD'];
        }
        for ($i = 0; $i < $s->infants; $i++) {
            $travelers[] = ['id' => (string) $id++, 'travelerType' => 'HELD_INFANT', 'associatedAdultId' => (string) (($i % max(1, $s->adults)) + 1)];
        }

        return $travelers;
    }

    private function orderTravelers(FlightOrder $order): array
    {
        [$code, $number] = $this->phone($order->contact_phone);
        $out = [];

        foreach ($this->orderedPassengers($order->loadMissing('passengers')) as $i => $p) {
            $contact = ['emailAddress' => $order->contact_email];
            if ($number) {
                $contact['phones'] = [['deviceType' => 'MOBILE', 'countryCallingCode' => $code, 'number' => $number]];
            }

            $t = [
                'id' => (string) ($i + 1),
                'dateOfBirth' => $p->birth_date->format('Y-m-d'),
                'name' => ['firstName' => strtoupper($p->first_name), 'lastName' => strtoupper($p->last_name)],
                'gender' => ($p->gender === 'F' || in_array($p->title, ['Mme', 'Mlle'], true)) ? 'FEMALE' : 'MALE',
                'contact' => $contact,
            ];

            if ($p->passport_number) {
                $country = strtoupper($p->nationality ?: 'CG');
                $t['documents'] = [[
                    'documentType' => 'PASSPORT', 'number' => $p->passport_number,
                    'expiryDate' => $p->passport_expiry?->format('Y-m-d'),
                    'issuanceCountry' => $country, 'validityCountry' => $country, 'nationality' => $country, 'holder' => true,
                ]];
            }

            $out[] = $t;
        }

        return $out;
    }

    /** @return array{0:string,1:?string} [indicatif, numero national] */
    private function phone(?string $phone): array
    {
        $default = (string) config('flights.amadeus.default_calling_code', '242');
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return [$default, null];
        }

        if (str_starts_with(trim((string) $phone), '+') || str_starts_with($digits, '00')) {
            $digits = preg_replace('/^00/', '', $digits);
            foreach ([3, 2, 1] as $len) { // indicatifs a 3, 2 puis 1 chiffre
                $prefix = substr($digits, 0, $len);
                if (in_array($prefix, ['242', '243', '237', '241', '225', '221', '236', '235', '244', '234', '233', '251', '254', '212', '216', '213', '971', '974', '90', '33', '32', '44', '49', '34', '39', '86', '1'], true)) {
                    return [$prefix, substr($digits, $len)];
                }
            }
        }

        return [$default, ltrim($digits, '0')];
    }

    private function segments(array $segments): array
    {
        return array_map(fn ($s) => [
            'from' => $s['departure']['iataCode'],
            'to' => $s['arrival']['iataCode'],
            'flight_number' => $s['carrierCode'].$s['number'],
            'departure' => $s['departure']['at'],
            'arrival' => $s['arrival']['at'],
            'duration_minutes' => isset($s['duration']) ? (int) CarbonInterval::make($s['duration'])->totalMinutes : 0,
            'stops' => (int) ($s['numberOfStops'] ?? 0),
        ], $segments);
    }

    private function fareRules(array $o): array
    {
        $bags = $o['travelerPricings'][0]['fareDetailsBySegment'][0]['includedCheckedBags'] ?? [];
        $baggage = isset($bags['quantity']) ? $bags['quantity'].' bagage(s) en soute'
            : (isset($bags['weight']) ? $bags['weight'].' '.($bags['weightUnit'] ?? 'KG').' en soute' : 'Bagage en soute selon tarif');

        return ['baggage' => $baggage, 'changes' => 'Modification selon conditions du tarif', 'refund' => 'Remboursement selon conditions du tarif'];
    }
}
