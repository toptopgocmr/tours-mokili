<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Travelport GALILEO (1G) via Travelport Universal API (SOAP / XML).
 *
 *  - Recherche : LowFareSearchReq (SolutionResult = true) -> une offre par
 *    AirPricingSolution, avec les vrais troncons (correspondances).
 *  - Revalidation du prix : AirPriceReq sur les memes segments / classes.
 *  - Reservation (PNR) et emission : realisees par l'equipe billetterie dans
 *    le terminal Galileo puis saisies dans le dossier (statut PENDING ici),
 *    comme pour les tarifs negocies. Le client ne voit jamais "billet emis"
 *    avant la saisie des numeros de billets.
 *
 * Identifiants (fournis par Travelport a l'agence) dans les variables
 * d'environnement Railway : GALILEO_USERNAME, GALILEO_PASSWORD,
 * GALILEO_TARGET_BRANCH, GALILEO_PCC, GALILEO_ENDPOINT. Jamais dans le code.
 */
class GalileoSupplier extends AbstractSupplier
{
    /** Noms des compagnies les plus frequentes au depart de Brazzaville. */
    public const AIRLINES = [
        'AF' => 'Air France', 'ET' => 'Ethiopian Airlines', 'TK' => 'Turkish Airlines', 'KQ' => 'Kenya Airways',
        'KP' => 'ASKY Airlines', 'WB' => 'RwandAir', 'SA' => 'South African Airways', 'EK' => 'Emirates',
        'QR' => 'Qatar Airways', 'FZ' => 'flydubai', 'MS' => 'EgyptAir', 'AT' => 'Royal Air Maroc',
        'SN' => 'Brussels Airlines', 'LH' => 'Lufthansa', 'KL' => 'KLM', 'TP' => 'TAP Air Portugal',
        'DT' => 'TAAG Angola Airlines', '8Q' => 'Air Congo', 'UR' => 'Uganda Airlines', 'HF' => 'Air Cote d\'Ivoire',
        'QC' => 'Camair-Co', 'SV' => 'Saudia', 'LO' => 'LOT Polish Airlines', 'BT' => 'airBaltic', 'OS' => 'Austrian Airlines',
        'LX' => 'Swiss', 'IB' => 'Iberia', 'UX' => 'Air Europa', 'CA' => 'Air China', 'EY' => 'Etihad Airways',
    ];

    protected const CABINS = ['economy' => 'Economy', 'premium' => 'PremiumEconomy', 'business' => 'Business', 'first' => 'First'];

    public static function configured(): bool
    {
        $c = config('flights.galileo');

        return ! empty($c['username']) && ! empty($c['password']) && ! empty($c['target_branch']);
    }

    // ------------------------------------------------------------- recherche

    public function search(SearchCriteria $c): array
    {
        $legs = '';
        foreach ($c->itinerary() as $leg) {
            $legs .= '<air:SearchAirLeg>'
                .'<air:SearchOrigin><com:CityOrAirport Code="'.$leg['origin'].'" PreferCity="true"/></air:SearchOrigin>'
                .'<air:SearchDestination><com:CityOrAirport Code="'.$leg['destination'].'" PreferCity="true"/></air:SearchDestination>'
                .'<air:SearchDepTime PreferredTime="'.$leg['date']->toDateString().'"/>'
                .'<air:AirLegModifiers><air:PreferredCabins><com:CabinClass Type="'.(self::CABINS[$c->cabin] ?? 'Economy').'"/></air:PreferredCabins></air:AirLegModifiers>'
                .'</air:SearchAirLeg>';
        }

        $body = '<air:LowFareSearchReq TargetBranch="'.e($this->cfg('target_branch')).'" AuthorizedBy="MOKILITOUR" '
            .'TraceId="'.Str::uuid().'" SolutionResult="true" '.$this->ns().'>'
            .'<com:BillingPointOfSaleInfo OriginApplication="UAPI"/>'
            .$legs
            .'<air:AirSearchModifiers MaxSolutions="'.(int) $this->cfg('max_solutions', 50).'">'
            .'<air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>'
            .'</air:AirSearchModifiers>'
            .$this->passengersXml($c)
            .'<air:AirPricingModifiers CurrencyType="'.e($this->cfg('currency', 'XAF')).'"/>'
            .'</air:LowFareSearchReq>';

        $xp = $this->call($body, allowNoAvailability: true);
        if (! $xp) {
            return [];
        }

        $segments = $this->segmentsByKey($xp);
        $fareInfos = $this->fareInfosByKey($xp);
        $offers = [];
        foreach ($xp->query('//*[local-name()="AirPricingSolution"]') as $sol) {
            $journeys = [];
            foreach ($xp->query('./*[local-name()="Journey"]', $sol) as $j) {
                $keys = [];
                foreach ($xp->query('./*[local-name()="AirSegmentRef"]', $j) as $ref) {
                    $keys[] = $ref->getAttribute('Key');
                }
                $journeys[] = $keys;
            }
            if (! $journeys) { // reponse sans Journey : on regroupe les segments par Group
                $byGroup = [];
                foreach ($xp->query('./*[local-name()="AirSegmentRef"]', $sol) as $ref) {
                    $seg = $segments[$ref->getAttribute('Key')] ?? null;
                    if ($seg) {
                        $byGroup[(int) $seg['group']][] = $ref->getAttribute('Key');
                    }
                }
                ksort($byGroup);
                $journeys = array_values($byGroup);
            }

            $offer = $this->offerFromSolution($xp, $sol, $journeys, $segments, $fareInfos, $c);
            if ($offer) {
                $offers[] = $offer;
            }
        }

        return $offers;
    }

    // ---------------------------------------------------- revalidation du prix

    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        $stored = $offer->raw['galileo']['segments'] ?? null;
        if (! $stored) {
            return null;
        }

        $segXml = '';
        $codes = '';
        foreach ($stored as $s) {
            $segXml .= '<air:AirSegment Key="'.e($s['key']).'" Group="'.(int) $s['group'].'" Carrier="'.e($s['carrier']).'" '
                .'FlightNumber="'.e($s['number']).'" Origin="'.e($s['origin']).'" Destination="'.e($s['destination']).'" '
                .'DepartureTime="'.e($s['departure']).'" ArrivalTime="'.e($s['arrival']).'" ProviderCode="1G"'
                .(! empty($s['class']) ? ' ClassOfService="'.e($s['class']).'"' : '').'/>';
            if (! empty($s['class'])) {
                $codes .= '<air:AirSegmentPricingModifiers AirSegmentRef="'.e($s['key']).'"><air:PermittedBookingCodes>'
                    .'<air:BookingCode Code="'.e($s['class']).'"/></air:PermittedBookingCodes></air:AirSegmentPricingModifiers>';
            }
        }

        $body = '<air:AirPriceReq TargetBranch="'.e($this->cfg('target_branch')).'" AuthorizedBy="MOKILITOUR" TraceId="'.Str::uuid().'" '.$this->ns().'>'
            .'<com:BillingPointOfSaleInfo OriginApplication="UAPI"/>'
            .'<air:AirItinerary>'.$segXml.'</air:AirItinerary>'
            .'<air:AirPricingModifiers CurrencyType="'.e($this->cfg('currency', 'XAF')).'"/>'
            .$this->passengersXml($c)
            .'<air:AirPricingCommand>'.$codes.'</air:AirPricingCommand>'
            .'</air:AirPriceReq>';

        $xp = $this->call($body, allowNoAvailability: true);
        if (! $xp) {
            return null;
        }
        $sol = $xp->query('//*[local-name()="AirPriceResult"]/*[local-name()="AirPricingSolution"]')->item(0)
            ?? $xp->query('//*[local-name()="AirPricingSolution"]')->item(0);
        if (! $sol instanceof DOMElement) {
            return null;
        }

        // Memes segments, nouveaux montants.
        $segments = $this->segmentsByKey($xp) ?: collect($stored)->mapWithKeys(fn ($s) => [$s['key'] => $this->segmentFromStored($s)])->all();
        foreach ($stored as $s) {
            $segments[$s['key']] ??= $this->segmentFromStored($s);
        }
        $journeys = collect($stored)->groupBy('group')->sortKeys()->map(fn ($g) => $g->pluck('key')->all())->values()->all();

        return $this->offerFromSolution($xp, $sol, $journeys, $segments, $this->fareInfosByKey($xp), $c, (string) $offer->supplier_offer_id);
    }

    // ------------------------------------- reservation / emission (equipe)

    public function book(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Reservation a creer par l'equipe billetterie dans Galileo (segments et classes dans le dossier).");
    }

    public function issue(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Emission a realiser par l'equipe billetterie dans Galileo.");
    }

    public function retrieve(FlightBooking $booking): SupplierResult
    {
        $tickets = $booking->tickets()->pluck('ticket_number', 'flight_passenger_id')->all();

        return new SupplierResult(
            $booking->pnr ? SupplierResult::SUCCESS : SupplierResult::PENDING,
            $booking->pnr,
            $booking->supplier_booking_ref,
            $tickets,
            null,
            [],
            $booking->pnr ? 'booked' : 'pending',
            $tickets ? 'issued' : ($booking->pnr ? 'pending' : 'none'),
        );
    }

    public function cancel(FlightBooking $booking): SupplierResult
    {
        return SupplierResult::pending("Annulation a realiser par l'equipe billetterie dans Galileo.");
    }

    // ------------------------------------------------------------ internes

    protected function cfg(string $key, mixed $default = null): mixed
    {
        return config('flights.galileo.'.$key) ?? $default;
    }

    protected function ns(): string
    {
        $air = $this->cfg('air_schema', 'air_v52_0');
        $common = str_replace('air_', 'common_', $air);

        return 'xmlns:air="http://www.travelport.com/schema/'.$air.'" xmlns:com="http://www.travelport.com/schema/'.$common.'"';
    }

    protected function passengersXml(SearchCriteria $c): string
    {
        $xml = str_repeat('<com:SearchPassenger Code="ADT"/>', $c->adults);
        $xml .= str_repeat('<com:SearchPassenger Code="CNN" Age="8"/>', $c->children);
        $xml .= str_repeat('<com:SearchPassenger Code="INF" Age="1"/>', $c->infants);

        return $xml;
    }

    /** Appel SOAP. null = "pas de disponibilite" (reponse normale, sans vol). */
    protected function call(string $body, bool $allowNoAvailability = false): ?DOMXPath
    {
        if (! self::configured()) {
            throw new RuntimeException('Galileo : identifiants Travelport non configures.');
        }

        $envelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/><soapenv:Body>'
            .$body.'</soapenv:Body></soapenv:Envelope>';

        $response = Http::withBasicAuth($this->cfg('username'), $this->cfg('password'))
            ->timeout((int) $this->cfg('timeout', 45))
            ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'Accept-Encoding' => 'gzip'])
            ->withBody($envelope, 'text/xml; charset=utf-8')
            ->post($this->cfg('endpoint'));

        $dom = new DOMDocument;
        if (! @$dom->loadXML((string) $response->body(), LIBXML_NONET)) {
            throw new RuntimeException('Galileo : reponse illisible (HTTP '.$response->status().').');
        }
        $xp = new DOMXPath($dom);

        $fault = $xp->query('//*[local-name()="Fault"]/*[local-name()="faultstring"]')->item(0);
        if ($fault) {
            $msg = trim($fault->textContent);
            if ($allowNoAvailability && preg_match('/no availab|no air segments|no fare|no valid fare|unable to fare/i', $msg)) {
                return null;
            }
            throw new RuntimeException('Galileo : '.mb_substr($msg, 0, 250));
        }
        if (! $response->successful()) {
            throw new RuntimeException('Galileo : HTTP '.$response->status());
        }

        return $xp;
    }

    protected function segmentsByKey(DOMXPath $xp): array
    {
        $out = [];
        foreach ($xp->query('//*[local-name()="AirSegmentList"]/*[local-name()="AirSegment"] | //*[local-name()="AirItinerary"]/*[local-name()="AirSegment"]') as $s) {
            $operating = $xp->query('./*[local-name()="CodeshareInfo"]', $s)->item(0);
            $out[$s->getAttribute('Key')] = [
                'key' => $s->getAttribute('Key'),
                'group' => (int) $s->getAttribute('Group'),
                'carrier' => $s->getAttribute('Carrier'),
                'number' => $s->getAttribute('FlightNumber'),
                'origin' => $s->getAttribute('Origin'),
                'destination' => $s->getAttribute('Destination'),
                'departure' => $s->getAttribute('DepartureTime'),
                'arrival' => $s->getAttribute('ArrivalTime'),
                'flight_time' => (int) $s->getAttribute('FlightTime'),
                'equipment' => $s->getAttribute('Equipment') ?: null,
                'class' => $s->getAttribute('ClassOfService') ?: null,
                'operating' => $operating?->getAttribute('OperatingCarrier') ?: null,
                'operating_name' => $operating ? trim($operating->textContent) : null,
            ];
        }

        return $out;
    }

    protected function segmentFromStored(array $s): array
    {
        return $s + ['flight_time' => 0, 'equipment' => null, 'operating' => null, 'operating_name' => null];
    }

    protected function fareInfosByKey(DOMXPath $xp): array
    {
        $out = [];
        foreach ($xp->query('//*[local-name()="FareInfo"]') as $f) {
            $bag = $xp->query('.//*[local-name()="BaggageAllowance"]', $f)->item(0);
            $baggage = null;
            if ($bag) {
                $pieces = $xp->query('./*[local-name()="NumberOfPieces"]', $bag)->item(0);
                $weight = $xp->query('./*[local-name()="MaxWeight"]', $bag)->item(0);
                if ($pieces && (int) $pieces->textContent > 0) {
                    $baggage = (int) $pieces->textContent.' bagage'.((int) $pieces->textContent > 1 ? 's' : '').' en soute';
                } elseif ($weight && $weight->getAttribute('Value')) {
                    $baggage = $weight->getAttribute('Value').' '.strtolower($weight->getAttribute('Unit') ?: 'kg').' en soute';
                }
            }
            if ($f->getAttribute('Key')) {
                $out[$f->getAttribute('Key')] = ['baggage' => $baggage, 'fare_basis' => $f->getAttribute('FareBasis') ?: null];
            }
        }

        return $out;
    }

    /** "XAF767094" -> [767094.0, "XAF"] */
    protected function money(?string $v): array
    {
        if (! $v || ! preg_match('/^([A-Z]{3})\s*([\d.]+)$/', trim($v), $m)) {
            return [0.0, null];
        }

        return [(float) $m[2], $m[1]];
    }

    protected function offerFromSolution(DOMXPath $xp, DOMElement $sol, array $journeys, array $segments, array $fareInfos, SearchCriteria $c, ?string $offerId = null): ?OfferData
    {
        [$total, $currency] = $this->money($sol->getAttribute('TotalPrice'));
        if ($total <= 0 || ! $journeys) {
            return null;
        }

        $tz = fn (string $iso) => CarbonImmutable::parse($iso);
        $slices = [];
        $stored = [];
        $classes = [];
        foreach ($xp->query('.//*[local-name()="BookingInfo"]', $sol) as $bi) {
            $classes[$bi->getAttribute('SegmentRef')] = $bi->getAttribute('BookingCode');
        }

        foreach ($journeys as $g => $keys) {
            $segs = array_values(array_filter(array_map(fn ($k) => $segments[$k] ?? null, $keys)));
            if (! $segs) {
                return null;
            }
            $first = $segs[0];
            $last = $segs[count($segs) - 1];
            $minutes = (int) round(($tz($last['arrival'])->getTimestamp() - $tz($first['departure'])->getTimestamp()) / 60);

            $slices[] = [
                'origin' => $first['origin'],
                'destination' => $last['destination'],
                'departure_at' => substr($first['departure'], 0, 16),
                'arrival_at' => substr($last['arrival'], 0, 16),
                'duration_minutes' => max(0, $minutes),
                'stops' => count($segs) - 1,
                'via' => count($segs) > 1 ? $segs[0]['destination'] : null,
                'stop_airports' => array_map(fn ($s) => $s['destination'], array_slice($segs, 0, -1)),
                'segments' => array_map(fn ($s) => [
                    'airline_code' => $s['carrier'],
                    'airline_name' => self::AIRLINES[$s['carrier']] ?? $s['carrier'],
                    'flight_number' => $s['carrier'].' '.$s['number'],
                    'origin' => $s['origin'],
                    'destination' => $s['destination'],
                    'departure_at' => substr($s['departure'], 0, 16),
                    'arrival_at' => substr($s['arrival'], 0, 16),
                    'duration_minutes' => $s['flight_time'] ?: null,
                    'aircraft' => $s['equipment'],
                    'operated_by' => $s['operating'] ? (self::AIRLINES[$s['operating']] ?? ($s['operating_name'] ?: $s['operating'])) : null,
                    'booking_class' => $classes[$s['key']] ?? $s['class'],
                    'cabin' => $c->cabin,
                ], $segs),
            ];
            foreach ($segs as $s) {
                $stored[] = [
                    'key' => $s['key'], 'group' => $g, 'carrier' => $s['carrier'], 'number' => $s['number'],
                    'origin' => $s['origin'], 'destination' => $s['destination'], 'departure' => $s['departure'],
                    'arrival' => $s['arrival'], 'class' => $classes[$s['key']] ?? $s['class'],
                ];
            }
        }

        // Prix par type de passager (AirPricingInfo).
        $byType = [];
        $refundable = true;
        $baggage = null;
        $changeFee = $cancelFee = null;
        $plating = null;
        foreach ($xp->query('./*[local-name()="AirPricingInfo"]', $sol) as $info) {
            [$amount] = $this->money($info->getAttribute('TotalPrice'));
            $count = max(1, $xp->query('./*[local-name()="PassengerType"]', $info)->length);
            $code = $xp->query('./*[local-name()="PassengerType"]', $info)->item(0)?->getAttribute('Code') ?: 'ADT';
            $type = match ($code) { 'CNN', 'CHD', 'C08' => 'CHD', 'INF', 'INS' => 'INF', default => 'ADT' };
            $byType[$type] = ['count' => $count, 'amount' => $amount];
            $refundable = $refundable && $info->getAttribute('Refundable') === 'true';
            $plating = $plating ?: ($info->getAttribute('PlatingCarrier') ?: null);
            foreach ($xp->query('./*[local-name()="FareInfoRef"]', $info) as $ref) {
                $baggage ??= $fareInfos[$ref->getAttribute('Key')]['baggage'] ?? null;
            }
            foreach ($xp->query('.//*[local-name()="FareInfo"]', $info) as $fi) {
                $baggage ??= $fareInfos[$fi->getAttribute('Key')]['baggage'] ?? null;
            }
            $chg = $xp->query('./*[local-name()="ChangePenalty"]/*[local-name()="Amount"]', $info)->item(0);
            $cxl = $xp->query('./*[local-name()="CancelPenalty"]/*[local-name()="Amount"]', $info)->item(0);
            $changeFee ??= $chg ? $this->money($chg->textContent)[0] : null;
            $cancelFee ??= $cxl ? $this->money($cxl->textContent)[0] : null;
        }

        // Le montant d'un AirPricingInfo est soit unitaire, soit pour tous les
        // passagers du type : on retient la lecture qui retombe sur le total.
        $perPax = array_sum(array_map(fn ($t) => $t['count'] * $t['amount'], $byType));
        $aggregate = array_sum(array_column($byType, 'amount'));
        $unit = fn ($t) => abs($perPax - $total) <= abs($aggregate - $total) ? $t['amount'] : $t['amount'] / $t['count'];
        $lines = [];
        foreach (['ADT', 'CHD', 'INF'] as $type) {
            if (isset($byType[$type])) {
                $lines[] = ['type' => $type, 'count' => $byType[$type]['count'], 'unit_amount' => round($unit($byType[$type]), 2)];
            }
        }
        if (! $lines) {
            $lines = $this->passengerLines($c, $total / max(1, $c->adults + $c->children), $total / max(1, $c->adults + $c->children), 0);
        }

        $validating = $plating ?: $slices[0]['segments'][0]['airline_code'];

        return new OfferData(
            supplierOfferId: $offerId ?? 'galileo:'.$sol->getAttribute('Key'),
            validatingAirline: $validating,
            airlineName: self::AIRLINES[$validating] ?? $validating,
            slices: $slices,
            passengers: $lines,
            supplierAmount: round($total, 2),
            currency: $currency ?? $this->cfg('currency', 'XAF'),
            fareRules: [
                'cabin' => $c->cabin,
                'baggage' => $baggage,
                'refundable' => $refundable,
                'changeable' => true,
                'change_fee' => $changeFee,
                'cancel_fee' => $cancelFee,
                'conditions' => 'Tarif Galileo : conditions de la compagnie '.(self::AIRLINES[$validating] ?? $validating).'.',
            ],
            ticketingDeadline: null,
            raw: ['galileo' => ['segments' => $stored, 'solution' => $sol->getAttribute('Key')]],
        );
    }
}
