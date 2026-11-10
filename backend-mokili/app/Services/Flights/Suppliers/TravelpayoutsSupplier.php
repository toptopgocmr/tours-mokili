<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TRAVELPAYOUTS / AVIASALES Data API (inscription gratuite, ouverte au Congo).
 *
 * Donne les vrais prix des vols trouves recemment par les utilisateurs
 * d'Aviasales (cache de quelques jours) : compagnie, numero de vol, heure de
 * depart, duree, nombre d'escales. Les prix sont donc INDICATIFS :
 *  - le client reserve et paie en XAF (Mobile Money) sur Mokili Tours ;
 *  - l'equipe billetterie achete le billet (lien Aviasales fourni dans le
 *    dossier) puis saisit PNR et numeros de billet ; si le prix a change,
 *    elle propose le nouveau prix au client (confirmer-prix).
 *
 * Jeton : travelpayouts.com > Profil > API token  ->  TRAVELPAYOUTS_TOKEN.
 * Marker (identifiant partenaire, pour les commissions) -> TRAVELPAYOUTS_MARKER.
 */
class TravelpayoutsSupplier extends AbstractSupplier
{
    public static function configured(): bool
    {
        return ! empty(config('flights.travelpayouts.token'));
    }

    public function search(SearchCriteria $c): array
    {
        $legs = $c->itinerary();

        // Aller-retour : l'API renvoie directement le prix du billet A/R.
        if ($c->tripType() === 'roundtrip' && count($legs) === 2) {
            $items = $this->prices($legs[0]['origin'], $legs[0]['destination'], $legs[0]['date']->toDateString(), $legs[1]['date']->toDateString());

            return collect($items)->map(fn ($i) => $this->toOffer([$i], $c, true))->filter()->values()->all();
        }

        // Aller simple / multi-destinations : un billet par troncon, combines.
        $perLeg = [];
        foreach ($legs as $l) {
            $items = $this->prices($l['origin'], $l['destination'], $l['date']->toDateString(), null);
            if (! $items) {
                return [];
            }
            $perLeg[] = $items;
        }
        if (count($perLeg) === 1) {
            return collect($perLeg[0])->map(fn ($i) => $this->toOffer([$i], $c))->filter()->values()->all();
        }

        // Multi-destinations : les 3 meilleurs prix par troncon (1 au-dela de 3 troncons).
        $keep = count($perLeg) <= 3 ? 3 : 1;
        $combos = [[]];
        foreach ($perLeg as $items) {
            $next = [];
            foreach ($combos as $combo) {
                foreach (array_slice($items, 0, $keep) as $i) {
                    $next[] = array_merge($combo, [$i]);
                }
            }
            $combos = $next;
        }

        return collect($combos)->map(fn ($combo) => $this->toOffer($combo, $c))->filter()
            ->sortBy(fn (OfferData $o) => $o->supplierAmount)->values()->all();
    }

    /** Prix en cache : on reprend l'offre telle quelle tant que le vol n'est pas parti. */
    public function price(FlightOffer $offer, SearchCriteria $c): ?OfferData
    {
        $tp = $offer->raw['travelpayouts'] ?? null;
        if (! $tp || empty($tp['items'])) {
            return null;
        }
        $first = $tp['items'][0]['departure_at'] ?? null;
        if ($first && CarbonImmutable::parse($first)->isPast()) {
            return null;
        }

        return $this->toOffer($tp['items'], $c, (bool) ($tp['roundtrip'] ?? false));
    }

    public function book(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Billet a acheter par l'equipe billetterie (lien Aviasales dans le dossier), puis saisir le PNR.");
    }

    public function issue(FlightBooking $booking, string $idempotencyKey): SupplierResult
    {
        return SupplierResult::pending("Emission a saisir par l'equipe billetterie.");
    }

    public function retrieve(FlightBooking $booking): SupplierResult
    {
        $tickets = $booking->tickets()->pluck('ticket_number', 'flight_passenger_id')->all();

        return new SupplierResult($booking->pnr ? SupplierResult::SUCCESS : SupplierResult::PENDING, $booking->pnr, $booking->supplier_booking_ref,
            $tickets, null, [], $booking->pnr ? 'booked' : 'pending', $tickets ? 'issued' : ($booking->pnr ? 'pending' : 'none'));
    }

    public function cancel(FlightBooking $booking): SupplierResult
    {
        return SupplierResult::pending("Annulation a realiser par l'equipe billetterie.");
    }

    // ------------------------------------------------------------ internes

    /** Billets les moins chers pour une date (aviasales/v3/prices_for_dates), en cache 30 min. */
    protected function prices(string $from, string $to, string $date, ?string $return): array
    {
        $params = array_filter([
            'origin' => $from,
            'destination' => $to,
            'departure_at' => $date,
            'return_at' => $return,
            'one_way' => $return ? 'false' : 'true',
            'direct' => 'false',
            'currency' => 'eur',
            'sorting' => 'price',
            'unique' => 'false',
            'limit' => (int) config('flights.travelpayouts.max_offers', 30),
            'page' => 1,
        ], fn ($v) => $v !== null);

        return Cache::remember('tp:'.md5(json_encode($params)), now()->addMinutes(30), function () use ($params) {
            $res = Http::baseUrl(config('flights.travelpayouts.endpoint', 'https://api.travelpayouts.com'))
                ->withHeaders(['X-Access-Token' => config('flights.travelpayouts.token'), 'Accept-Encoding' => 'gzip, deflate'])
                ->acceptJson()
                ->timeout((int) config('flights.travelpayouts.timeout', 20))
                ->get('/aviasales/v3/prices_for_dates', $params);

            if (! $res->successful() || $res->json('success') === false) {
                throw new RuntimeException('Travelpayouts : '.($res->json('error') ?? 'HTTP '.$res->status()));
            }

            // Seulement les vols du jour demande (l'API peut renvoyer le mois).
            return array_values(array_filter($res->json('data') ?? [],
                fn ($i) => substr((string) ($i['departure_at'] ?? ''), 0, 10) === $params['departure_at']));
        });
    }

    protected function slice(string $from, string $to, ?string $depIso, int $minutes, int $stops, ?string $airline, $number, string $cabin): ?array
    {
        if (! $depIso) {
            return null;
        }
        $dep = CarbonImmutable::parse($depIso);
        $local = $dep->format('Y-m-d\TH:i');
        // Heure locale d'arrivee inconnue (fuseau) : on garde le fuseau de depart.
        $arr = $minutes ? $dep->addMinutes($minutes)->format('Y-m-d\TH:i') : null;
        $code = $airline ? strtoupper($airline) : null;

        return [
            'origin' => $from,
            'destination' => $to,
            'departure_at' => $local,
            'arrival_at' => $arr ?? $local,
            'duration_minutes' => $minutes,
            'stops' => $stops,
            'via' => null,
            'stop_airports' => [],
            'segments' => [[
                'airline_code' => $code,
                'airline_name' => null,
                'flight_number' => trim(($code ?? '').' '.($number ?? '')),
                'origin' => $from,
                'destination' => $to,
                'departure_at' => $local,
                'arrival_at' => $arr ?? $local,
                'duration_minutes' => $minutes ?: null,
                'aircraft' => null,
                'operated_by' => null,
                'cabin' => $cabin,
            ]],
        ];
    }

    /** @param array $items billets Aviasales (1 par troncon, ou 1 billet A/R) */
    protected function toOffer(array $items, SearchCriteria $c, bool $roundtrip = false): ?OfferData
    {
        $rate = (float) config('flights.travelpayouts.eur_xaf', 655.957);
        $slices = [];
        $eur = 0.0;
        $links = [];
        foreach ($items as $i) {
            if (! isset($i['price'])) {
                return null;
            }
            $eur += (float) $i['price'];
            $from = $i['origin_airport'] ?? $i['origin'];
            $to = $i['destination_airport'] ?? $i['destination'];
            $out = $this->slice($from, $to, $i['departure_at'] ?? null, (int) ($i['duration_to'] ?? $i['duration'] ?? 0),
                (int) ($i['transfers'] ?? 0), $i['airline'] ?? null, $i['flight_number'] ?? null, $c->cabin);
            if (! $out) {
                return null;
            }
            $slices[] = $out;
            if ($roundtrip) {
                $back = $this->slice($to, $from, $i['return_at'] ?? null, (int) ($i['duration_back'] ?? 0),
                    (int) ($i['return_transfers'] ?? 0), $i['airline'] ?? null, null, $c->cabin);
                if (! $back) {
                    return null;
                }
                $slices[] = $back;
            }
            if (! empty($i['link'])) {
                $links[] = $this->deeplink($i['link']);
            }
        }

        // Prix Aviasales = 1 adulte. Enfant ~75 %, bebe ~10 % (indicatif).
        $adult = round($eur * $rate);
        $child = round($adult * 0.75);
        $infant = round($adult * 0.10);
        $lines = $this->passengerLines($c, $adult, $child, $infant);

        $owner = $slices[0]['segments'][0]['airline_code'] ?? null;
        $key = implode('|', array_map(fn ($i) => ($i['airline'] ?? '').($i['flight_number'] ?? '').'@'.($i['departure_at'] ?? ''), $items));

        return new OfferData(
            supplierOfferId: 'tp:'.substr(md5($key.($roundtrip ? 'rt' : '')), 0, 20),
            validatingAirline: $owner,
            airlineName: $owner,
            slices: $slices,
            passengers: $lines,
            supplierAmount: $this->linesTotal($lines),
            currency: 'XAF',
            fareRules: [
                'cabin' => $c->cabin,
                'baggage' => null,
                'cabin_baggage' => null,
                'refundable' => false,
                'changeable' => false,
                'indicative' => true,
                'conditions' => 'Prix indicatif constate recemment sur Aviasales ('.round($eur, 2).' EUR / adulte). '
                    .'Le prix exact, les bagages et les conditions sont confirmes par notre equipe avant emission du billet.',
            ],
            raw: ['travelpayouts' => [
                'items' => $items,
                'roundtrip' => $roundtrip,
                'price_eur' => $eur,
                'links' => $links,
            ]],
        );
    }

    protected function deeplink(string $path): string
    {
        $url = 'https://www.aviasales.com'.$path;
        $marker = config('flights.travelpayouts.marker');

        return $marker ? $url.(str_contains($url, '?') ? '&' : '?').'marker='.urlencode($marker) : $url;
    }
}
