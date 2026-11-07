<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Aeroports de reference + fournisseur de simulation (sandbox). Garde
// idempotente : rejouable sans doublon.
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $airports = [
            ['BZV', 'Maya-Maya', 'Brazzaville', 'CG', 'Africa/Brazzaville'],
            ['PNR', 'Agostinho-Neto', 'Pointe-Noire', 'CG', 'Africa/Brazzaville'],
            ['FIH', "N'djili", 'Kinshasa', 'CD', 'Africa/Kinshasa'],
            ['FBM', 'Lubumbashi', 'Lubumbashi', 'CD', 'Africa/Lubumbashi'],
            ['GOM', 'Goma', 'Goma', 'CD', 'Africa/Lubumbashi'],
            ['DLA', 'Douala', 'Douala', 'CM', 'Africa/Douala'],
            ['NSI', 'Yaounde Nsimalen', 'Yaounde', 'CM', 'Africa/Douala'],
            ['LBV', "Leon M'ba", 'Libreville', 'GA', 'Africa/Libreville'],
            ['POG', 'Port-Gentil', 'Port-Gentil', 'GA', 'Africa/Libreville'],
            ['SSG', 'Malabo', 'Malabo', 'GQ', 'Africa/Malabo'],
            ['BGF', "Bangui M'poko", 'Bangui', 'CF', 'Africa/Bangui'],
            ['NDJ', 'Ndjamena', 'Ndjamena', 'TD', 'Africa/Ndjamena'],
            ['LAD', 'Luanda', 'Luanda', 'AO', 'Africa/Luanda'],
            ['ABJ', 'Felix Houphouet-Boigny', 'Abidjan', 'CI', 'Africa/Abidjan'],
            ['DSS', 'Blaise Diagne', 'Dakar', 'SN', 'Africa/Dakar'],
            ['LOS', 'Murtala Muhammed', 'Lagos', 'NG', 'Africa/Lagos'],
            ['ACC', 'Kotoka', 'Accra', 'GH', 'Africa/Accra'],
            ['ADD', 'Bole', 'Addis-Abeba', 'ET', 'Africa/Addis_Ababa'],
            ['NBO', 'Jomo Kenyatta', 'Nairobi', 'KE', 'Africa/Nairobi'],
            ['JNB', 'O. R. Tambo', 'Johannesburg', 'ZA', 'Africa/Johannesburg'],
            ['CMN', 'Mohammed V', 'Casablanca', 'MA', 'Africa/Casablanca'],
            ['CAI', 'Le Caire', 'Le Caire', 'EG', 'Africa/Cairo'],
            ['CDG', 'Charles de Gaulle', 'Paris', 'FR', 'Europe/Paris'],
            ['BRU', 'Bruxelles National', 'Bruxelles', 'BE', 'Europe/Brussels'],
            ['LHR', 'Heathrow', 'Londres', 'GB', 'Europe/London'],
            ['IST', 'Istanbul', 'Istanbul', 'TR', 'Europe/Istanbul'],
            ['DXB', 'Dubai International', 'Dubai', 'AE', 'Asia/Dubai'],
            ['DOH', 'Hamad', 'Doha', 'QA', 'Asia/Qatar'],
            ['JFK', 'John F. Kennedy', 'New York', 'US', 'America/New_York'],
            ['YUL', 'Montreal-Trudeau', 'Montreal', 'CA', 'America/Toronto'],
            ['PEK', 'Capital', 'Pekin', 'CN', 'Asia/Shanghai'],
        ];

        foreach ($airports as [$iata, $name, $city, $country, $tz]) {
            if (DB::table('airports')->where('iata', $iata)->exists()) {
                continue;
            }
            DB::table('airports')->insert([
                'iata' => $iata, 'name' => $name, 'city' => $city, 'country' => $country,
                'timezone' => $tz, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        if (! DB::table('flight_suppliers')->where('code', 'sandbox')->exists()) {
            DB::table('flight_suppliers')->insert([
                'code' => 'sandbox',
                'name' => 'Fournisseur de simulation (sandbox)',
                'driver' => 'fake',
                'is_active' => true,
                'connection_status' => 'ok',
                'settings' => json_encode(['note' => 'A remplacer par le consolidateur reel une fois le contrat signe.']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // No-op : donnees de reference.
    }
};
