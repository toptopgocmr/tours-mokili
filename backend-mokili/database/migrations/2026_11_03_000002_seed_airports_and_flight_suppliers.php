<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Migration de donnees (Railway ne lance que les migrations) : aeroports
// de depart / destination courants depuis le Congo et fournisseurs de base.
return new class extends Migration
{
    public const AIRPORTS = [
        // code, nom, ville, pays, nom pays, fuseau, popularite
        ['BZV', 'Maya-Maya', 'Brazzaville', 'CG', 'Congo', 'Africa/Brazzaville', 100],
        ['PNR', 'Agostinho-Neto', 'Pointe-Noire', 'CG', 'Congo', 'Africa/Brazzaville', 95],
        ['OLL', 'Oyo Ollombo', 'Ollombo', 'CG', 'Congo', 'Africa/Brazzaville', 40],
        ['DIS', 'Ngot Nzoungou', 'Dolisie', 'CG', 'Congo', 'Africa/Brazzaville', 35],
        ['OUE', 'Ouesso', 'Ouesso', 'CG', 'Congo', 'Africa/Brazzaville', 30],
        ['FIH', "N'djili", 'Kinshasa', 'CD', 'RD Congo', 'Africa/Kinshasa', 90],
        ['FBM', 'Luano', 'Lubumbashi', 'CD', 'RD Congo', 'Africa/Lubumbashi', 50],
        ['DLA', 'Douala International', 'Douala', 'CM', 'Cameroun', 'Africa/Douala', 85],
        ['NSI', 'Yaounde Nsimalen', 'Yaounde', 'CM', 'Cameroun', 'Africa/Douala', 70],
        ['LBV', "Leon-Mba", 'Libreville', 'GA', 'Gabon', 'Africa/Libreville', 70],
        ['LAD', 'Quatro de Fevereiro', 'Luanda', 'AO', 'Angola', 'Africa/Luanda', 65],
        ['SSG', 'Malabo', 'Malabo', 'GQ', 'Guinee equatoriale', 'Africa/Malabo', 40],
        ['BGF', "Bangui M'Poko", 'Bangui', 'CF', 'Centrafrique', 'Africa/Bangui', 40],
        ['NDJ', "N'Djamena", "N'Djamena", 'TD', 'Tchad', 'Africa/Ndjamena', 40],
        ['LFW', 'Lome-Tokoin', 'Lome', 'TG', 'Togo', 'Africa/Lome', 70],
        ['COO', 'Cotonou Cadjehoun', 'Cotonou', 'BJ', 'Benin', 'Africa/Porto-Novo', 60],
        ['ABJ', 'Felix-Houphouet-Boigny', 'Abidjan', 'CI', "Cote d'Ivoire", 'Africa/Abidjan', 80],
        ['DSS', 'Blaise-Diagne', 'Dakar', 'SN', 'Senegal', 'Africa/Dakar', 75],
        ['BKO', 'Modibo-Keita', 'Bamako', 'ML', 'Mali', 'Africa/Bamako', 45],
        ['OUA', 'Ouagadougou', 'Ouagadougou', 'BF', 'Burkina Faso', 'Africa/Ouagadougou', 40],
        ['ACC', 'Kotoka', 'Accra', 'GH', 'Ghana', 'Africa/Accra', 60],
        ['LOS', 'Murtala-Muhammed', 'Lagos', 'NG', 'Nigeria', 'Africa/Lagos', 65],
        ['ADD', 'Bole', 'Addis-Abeba', 'ET', 'Ethiopie', 'Africa/Addis_Ababa', 85],
        ['KGL', 'Kigali International', 'Kigali', 'RW', 'Rwanda', 'Africa/Kigali', 70],
        ['NBO', 'Jomo-Kenyatta', 'Nairobi', 'KE', 'Kenya', 'Africa/Nairobi', 70],
        ['JNB', 'O.R. Tambo', 'Johannesburg', 'ZA', 'Afrique du Sud', 'Africa/Johannesburg', 75],
        ['CMN', 'Mohammed V', 'Casablanca', 'MA', 'Maroc', 'Africa/Casablanca', 80],
        ['TUN', 'Tunis-Carthage', 'Tunis', 'TN', 'Tunisie', 'Africa/Tunis', 50],
        ['CAI', 'Le Caire', 'Le Caire', 'EG', 'Egypte', 'Africa/Cairo', 55],
        ['CDG', 'Charles-de-Gaulle', 'Paris', 'FR', 'France', 'Europe/Paris', 95],
        ['ORY', 'Orly', 'Paris', 'FR', 'France', 'Europe/Paris', 70],
        ['MRS', 'Marseille Provence', 'Marseille', 'FR', 'France', 'Europe/Paris', 40],
        ['BRU', 'Bruxelles', 'Bruxelles', 'BE', 'Belgique', 'Europe/Brussels', 75],
        ['LIS', 'Humberto Delgado', 'Lisbonne', 'PT', 'Portugal', 'Europe/Lisbon', 45],
        ['MAD', 'Madrid-Barajas', 'Madrid', 'ES', 'Espagne', 'Europe/Madrid', 40],
        ['FCO', 'Fiumicino', 'Rome', 'IT', 'Italie', 'Europe/Rome', 40],
        ['FRA', 'Francfort', 'Francfort', 'DE', 'Allemagne', 'Europe/Berlin', 45],
        ['LHR', 'Heathrow', 'Londres', 'GB', 'Royaume-Uni', 'Europe/London', 50],
        ['GVA', 'Geneve', 'Geneve', 'CH', 'Suisse', 'Europe/Zurich', 40],
        ['IST', 'Istanbul', 'Istanbul', 'TR', 'Turquie', 'Europe/Istanbul', 80],
        ['DXB', 'Dubai International', 'Dubai', 'AE', 'Emirats arabes unis', 'Asia/Dubai', 85],
        ['DOH', 'Hamad', 'Doha', 'QA', 'Qatar', 'Asia/Qatar', 50],
        ['JED', 'King Abdulaziz', 'Djeddah', 'SA', 'Arabie saoudite', 'Asia/Riyadh', 45],
        ['CAN', 'Baiyun', 'Canton (Guangzhou)', 'CN', 'Chine', 'Asia/Shanghai', 75],
        ['PEK', 'Pekin Capitale', 'Pekin', 'CN', 'Chine', 'Asia/Shanghai', 55],
        ['PVG', 'Pudong', 'Shanghai', 'CN', 'Chine', 'Asia/Shanghai', 55],
        ['BOM', 'Chhatrapati Shivaji', 'Mumbai', 'IN', 'Inde', 'Asia/Kolkata', 40],
        ['JFK', 'John F. Kennedy', 'New York', 'US', 'Etats-Unis', 'America/New_York', 60],
        ['IAD', 'Washington Dulles', 'Washington', 'US', 'Etats-Unis', 'America/New_York', 45],
        ['YUL', 'Montreal-Trudeau', 'Montreal', 'CA', 'Canada', 'America/Toronto', 60],
        ['GRU', 'Guarulhos', 'Sao Paulo', 'BR', 'Bresil', 'America/Sao_Paulo', 35],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::AIRPORTS as [$code, $name, $city, $country, $countryName, $tz, $pop]) {
            DB::table('airports')->updateOrInsert(['iata_code' => $code], [
                'name' => $name, 'city' => $city, 'country_code' => $country,
                'country_name' => $countryName, 'timezone' => $tz, 'popularity' => $pop,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // "manuel" : tarifs negocies saisis dans le back-office, reservation
        // et emission faites par l'equipe billetterie chez le consolidateur.
        DB::table('flight_suppliers')->updateOrInsert(['code' => 'manuel'], [
            'name' => 'Tarifs negocies MOKILI TOUR (consolidateur)',
            'driver' => 'manual', 'is_active' => true, 'priority' => 10,
            'connection_status' => 'ok', 'created_at' => $now, 'updated_at' => $now,
        ]);

        // "sandbox" : fournisseur de test (PNR et billets fictifs). Utilisable
        // uniquement si FLIGHTS_SANDBOX=true - jamais en production.
        DB::table('flight_suppliers')->updateOrInsert(['code' => 'sandbox'], [
            'name' => 'Sandbox (tests uniquement)',
            'driver' => 'sandbox', 'is_active' => false, 'priority' => 99,
            'connection_status' => 'unknown', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('flight_suppliers')->whereIn('code', ['manuel', 'sandbox'])->delete();
        DB::table('airports')->whereIn('iata_code', array_column(self::AIRPORTS, 0))->delete();
    }
};
