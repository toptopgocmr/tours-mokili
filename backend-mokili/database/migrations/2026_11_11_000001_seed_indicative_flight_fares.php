<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Billetterie : grille de tarifs indicatifs pour le fournisseur "manuel".
 *
 * Tant qu'aucune API fournisseur n'est branchee sur Railway (Galileo, Duffel,
 * Travelpayouts), la recherche de vols renvoyait 0 resultat. Cette migration
 * remplit /admin/vols/tarifs avec les principales liaisons au depart et a
 * destination de Brazzaville et Pointe-Noire (compagnies qui desservent le
 * Congo, via leur hub), en economique et en affaires, aller et retour.
 *
 * Prix et horaires sont INDICATIFS : la reservation reste confirmee puis
 * emise par l'equipe billetterie (fournisseur manuel = statut PENDING jusqu'a
 * la saisie du PNR). Tout se modifie ou se desactive dans le back-office ;
 * relancer la migration ne touche pas aux tarifs saisis a la main.
 */
return new class extends Migration
{
    public const FAMILY = 'Tarif indicatif';

    /** IATA => [latitude, longitude, fuseau] */
    private const AIRPORTS = [
        'BZV' => [-4.25, 15.25, 'Africa/Brazzaville'], 'PNR' => [-4.82, 11.89, 'Africa/Brazzaville'],
        'OLL' => [-1.23, 15.91, 'Africa/Brazzaville'], 'OUE' => [1.62, 16.04, 'Africa/Brazzaville'],
        'DLA' => [4.01, 9.72, 'Africa/Douala'], 'LBV' => [0.46, 9.41, 'Africa/Libreville'],
        'LFW' => [6.17, 1.25, 'Africa/Lome'], 'COO' => [6.36, 2.38, 'Africa/Porto-Novo'],
        'ABJ' => [5.26, -3.93, 'Africa/Abidjan'], 'DSS' => [14.67, -17.07, 'Africa/Dakar'],
        'BKO' => [12.53, -7.95, 'Africa/Bamako'], 'OUA' => [12.35, -1.51, 'Africa/Ouagadougou'],
        'ACC' => [5.61, -0.17, 'Africa/Accra'], 'LOS' => [6.58, 3.32, 'Africa/Lagos'],
        'ADD' => [8.98, 38.80, 'Africa/Addis_Ababa'], 'KGL' => [-1.97, 30.14, 'Africa/Kigali'],
        'NBO' => [-1.32, 36.93, 'Africa/Nairobi'], 'JNB' => [-26.14, 28.25, 'Africa/Johannesburg'],
        'CMN' => [33.37, -7.59, 'Africa/Casablanca'], 'TUN' => [36.85, 10.23, 'Africa/Tunis'],
        'CAI' => [30.12, 31.41, 'Africa/Cairo'], 'CDG' => [49.01, 2.55, 'Europe/Paris'],
        'ORY' => [48.72, 2.38, 'Europe/Paris'], 'MRS' => [43.44, 5.22, 'Europe/Paris'],
        'BRU' => [50.90, 4.48, 'Europe/Brussels'], 'LIS' => [38.77, -9.13, 'Europe/Lisbon'],
        'MAD' => [40.47, -3.56, 'Europe/Madrid'], 'FCO' => [41.80, 12.25, 'Europe/Rome'],
        'FRA' => [50.03, 8.56, 'Europe/Berlin'], 'LHR' => [51.47, -0.45, 'Europe/London'],
        'GVA' => [46.24, 6.11, 'Europe/Zurich'], 'IST' => [41.26, 28.74, 'Europe/Istanbul'],
        'DXB' => [25.25, 55.36, 'Asia/Dubai'], 'DOH' => [25.27, 51.61, 'Asia/Qatar'],
        'JED' => [21.68, 39.16, 'Asia/Riyadh'], 'CAN' => [23.39, 113.30, 'Asia/Shanghai'],
        'PEK' => [40.08, 116.58, 'Asia/Shanghai'], 'PVG' => [31.14, 121.81, 'Asia/Shanghai'],
        'BOM' => [19.09, 72.87, 'Asia/Kolkata'], 'JFK' => [40.64, -73.78, 'America/New_York'],
        'IAD' => [38.95, -77.46, 'America/New_York'], 'YUL' => [45.47, -73.74, 'America/Toronto'],
        'GRU' => [-23.43, -46.47, 'America/Sao_Paulo'],
    ];

    /**
     * Compagnies : hub de correspondance, villes du Congo desservies, heures de
     * depart (depuis le Congo / vers le Congo), coefficient de prix, bagages,
     * destinations (le hub lui-meme = vol direct).
     */
    private const CARRIERS = [
        'ET' => [
            'name' => 'Ethiopian Airlines', 'hub' => 'ADD', 'congo' => ['BZV', 'PNR'],
            'out' => '13:40', 'in' => '09:15', 'coef' => 1.0, 'bag' => '2 x 23 kg', 'cabin_bag' => '1 x 7 kg', 'business' => true,
            'to' => ['ADD', 'DXB', 'DOH', 'JED', 'CAI', 'IST', 'NBO', 'KGL', 'JNB', 'BOM', 'CAN', 'PEK', 'PVG',
                'CDG', 'BRU', 'FRA', 'LHR', 'GVA', 'FCO', 'MAD', 'JFK', 'IAD', 'YUL', 'GRU', 'LOS', 'ACC', 'ABJ', 'DSS', 'LFW'],
        ],
        'AF' => [
            'name' => 'Air France', 'hub' => 'CDG', 'congo' => ['BZV', 'PNR'],
            'out' => '23:10', 'in' => '10:50', 'coef' => 1.15, 'bag' => '1 x 23 kg', 'cabin_bag' => '1 x 12 kg', 'business' => true,
            'to' => ['CDG', 'BRU', 'GVA', 'MRS', 'LHR', 'FRA', 'MAD', 'FCO', 'LIS', 'JFK', 'IAD', 'YUL'],
        ],
        'AT' => [
            'name' => 'Royal Air Maroc', 'hub' => 'CMN', 'congo' => ['BZV'],
            'out' => '03:55', 'in' => '22:30', 'coef' => 0.95, 'bag' => '2 x 23 kg', 'cabin_bag' => '1 x 10 kg', 'business' => true,
            'to' => ['CMN', 'CDG', 'ORY', 'BRU', 'MAD', 'LIS', 'FCO', 'FRA', 'LHR', 'GVA', 'MRS', 'TUN', 'CAI', 'IST',
                'DSS', 'ABJ', 'BKO', 'OUA', 'JFK', 'YUL', 'IAD'],
        ],
        'WB' => [
            'name' => 'RwandAir', 'hub' => 'KGL', 'congo' => ['BZV'],
            'out' => '12:05', 'in' => '08:10', 'coef' => 0.97, 'bag' => '2 x 23 kg', 'cabin_bag' => '1 x 7 kg', 'business' => true,
            'to' => ['KGL', 'NBO', 'JNB', 'DXB', 'LOS', 'ACC'],
        ],
        'KP' => [
            'name' => 'ASKY Airlines', 'hub' => 'LFW', 'congo' => ['BZV'],
            'out' => '09:20', 'in' => '06:45', 'coef' => 1.0, 'bag' => '2 x 23 kg', 'cabin_bag' => '1 x 7 kg', 'business' => true,
            'to' => ['LFW', 'ABJ', 'COO', 'DSS', 'ACC', 'LOS', 'BKO', 'OUA', 'DLA', 'LBV'],
        ],
        'Q8' => [
            'name' => 'Trans Air Congo', 'hub' => null, 'congo' => ['BZV'],
            'out' => '07:30', 'in' => '10:30', 'coef' => 1.0, 'bag' => '1 x 20 kg', 'cabin_bag' => '1 x 5 kg', 'business' => false,
            'to' => ['PNR', 'OLL', 'OUE'],
        ],
    ];

    public function up(): void
    {
        if (app()->environment('testing') || ! Schema::hasTable('flight_fares')) {
            return; // les tests creent leurs propres tarifs
        }

        $supplierId = DB::table('flight_suppliers')->where('code', 'manuel')->value('id');
        if (! $supplierId) {
            $supplierId = DB::table('flight_suppliers')->insertGetId([
                'code' => 'manuel', 'name' => 'Tarifs negocies MOKILI TOUR (consolidateur)',
                'driver' => 'manual', 'is_active' => true, 'priority' => 10,
                'connection_status' => 'ok', 'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            DB::table('flight_suppliers')->where('id', $supplierId)->update(['is_active' => true]);
        }

        // Relance idempotente : on remplace uniquement la grille indicative.
        DB::table('flight_fares')->where('flight_supplier_id', $supplierId)->where('fare_family', self::FAMILY)->delete();

        $rows = [];
        foreach (self::CARRIERS as $code => $c) {
            foreach ($c['congo'] as $congo) {
                foreach ($c['to'] as $dest) {
                    if ($dest === $congo) {
                        continue;
                    }
                    foreach ([[$congo, $dest, $c['out']], [$dest, $congo, $c['in']]] as [$from, $to, $time]) {
                        foreach ($c['business'] ? ['economy', 'business'] : ['economy'] as $cabin) {
                            $rows[] = $this->fare($supplierId, $code, $c, $from, $to, $time, $cabin);
                        }
                    }
                }
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('flight_fares')->insert($chunk);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('flight_fares')) {
            DB::table('flight_fares')->where('fare_family', self::FAMILY)->delete();
        }
    }

    private function fare(int $supplierId, string $code, array $c, string $from, string $to, string $time, string $cabin): array
    {
        $hub = $c['hub'];
        $direct = $hub === null || $from === $hub || $to === $hub;
        $legs = $direct ? [[$from, $to]] : [[$from, $hub], [$hub, $to]];

        $km = 0;
        $minutes = 0;
        foreach ($legs as [$a, $b]) {
            $d = $this->distance($a, $b);
            $km += $d;
            $minutes += (int) round($d / 780 * 60) + 30; // croisiere + roulage / montee
        }
        if (! $direct) {
            $minutes += 150; // correspondance au hub
        }
        $minutes = (int) (ceil($minutes / 5) * 5);

        // Heure d'arrivee locale (date de reference hors heure d'ete).
        $dep = CarbonImmutable::parse('2026-01-15 '.$time, self::AIRPORTS[$from][2]);
        $arr = $dep->addMinutes($minutes)->setTimezone(self::AIRPORTS[$to][2]);
        $dayOffset = (int) CarbonImmutable::parse($dep->toDateString(), 'UTC')
            ->diffInDays(CarbonImmutable::parse($arr->toDateString(), 'UTC'), false);

        // Prix selon la distance origine -> destination (pas le detour par le hub).
        $domestic = $code === 'Q8';
        $gc = $this->distance($from, $to);
        $base = $domestic ? 35000 + 70 * $gc : (30000 + 55 * $gc) * $c['coef'];
        if ($cabin === 'business') {
            $base *= 2.8;
        }
        $base = $this->round($base);
        $taxes = $this->round($domestic ? 15000 : $base * 0.2 + 50000);

        return [
            'flight_supplier_id' => $supplierId,
            'airline_code' => $code,
            'airline_name' => $c['name'],
            'flight_number' => null,
            'origin' => $from,
            'destination' => $to,
            'departure_time' => $time,
            'arrival_time' => $arr->format('H:i'),
            'arrival_day_offset' => max(0, $dayOffset),
            'duration_minutes' => $minutes,
            'stops' => $direct ? 0 : 1,
            'via' => $direct ? null : $hub,
            'days_of_week' => null,
            'valid_from' => null,
            'valid_to' => null,
            'cabin' => $cabin,
            'fare_family' => self::FAMILY,
            'base_fare' => $base,
            'taxes' => $taxes,
            'child_percent' => 75,
            'infant_percent' => 10,
            'currency' => 'XAF',
            'baggage' => $cabin === 'business' ? '2 x 32 kg' : $c['bag'],
            'cabin_baggage' => $c['cabin_bag'],
            'refundable' => false,
            'changeable' => true,
            'change_fee' => $cabin === 'business' ? 0 : ($domestic ? 10000 : 50000),
            'cancel_fee' => null,
            'conditions' => "Tarif indicatif : prix, horaires et disponibilite sont confirmes par la billetterie MOKILI TOUR avant l'emission du billet.",
            'seats_available' => null,
            'ticketing_delay_hours' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function distance(string $a, string $b): float
    {
        [$lat1, $lon1] = self::AIRPORTS[$a];
        [$lat2, $lon2] = self::AIRPORTS[$b];
        $p = M_PI / 180;
        $h = sin(($lat2 - $lat1) * $p / 2) ** 2 + cos($lat1 * $p) * cos($lat2 * $p) * sin(($lon2 - $lon1) * $p / 2) ** 2;

        return 2 * 6371 * asin(min(1, sqrt($h)));
    }

    private function round(float $amount): int
    {
        return (int) (ceil($amount / 500) * 500);
    }
};
