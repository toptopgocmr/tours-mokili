<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Demo content for the 5 non-Voyage modules (Logement, Voiture,
// Divertissement, Marketplace, Fret) so their new list/detail screens
// in the Flutter app aren't empty the first time they ship - see the
// mobile "quand je clic sur chaque service je dois voir les interfaces
// respectives" request. Each table is only seeded if it's currently
// empty, so this is safe to leave in place permanently (won't duplicate
// rows on future deploys, and won't touch real partner-submitted data
// once that exists).
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (DB::table('lodging_listings')->count() === 0) {
            $listings = [
                ['title' => 'Appartement moderne au bord de mer', 'city' => 'Douala', 'country' => 'CM', 'price_per_night' => 35000, 'bedrooms' => 2, 'bathrooms' => 1, 'max_guests' => 4, 'amenities' => ['wifi', 'climatisation', 'parking']],
                ['title' => 'Villa avec piscine privee', 'city' => 'Yaounde', 'country' => 'CM', 'price_per_night' => 75000, 'bedrooms' => 4, 'bathrooms' => 3, 'max_guests' => 8, 'amenities' => ['wifi', 'piscine', 'parking', 'climatisation']],
                ['title' => 'Studio cosy centre-ville', 'city' => 'Kinshasa', 'country' => 'CD', 'price_per_night' => 22000, 'bedrooms' => 1, 'bathrooms' => 1, 'max_guests' => 2, 'amenities' => ['wifi']],
                ['title' => 'Suite executive avec vue', 'city' => 'Libreville', 'country' => 'GA', 'price_per_night' => 60000, 'bedrooms' => 1, 'bathrooms' => 1, 'max_guests' => 2, 'amenities' => ['wifi', 'climatisation', 'petit-dejeuner']],
                ['title' => 'Maison familiale avec jardin', 'city' => 'Brazzaville', 'country' => 'CG', 'price_per_night' => 45000, 'bedrooms' => 3, 'bathrooms' => 2, 'max_guests' => 6, 'amenities' => ['wifi', 'parking', 'jardin']],
                ['title' => 'Appartement design pres de la tour Eiffel', 'city' => 'Paris', 'country' => 'FR', 'price_per_night' => 95000, 'bedrooms' => 2, 'bathrooms' => 1, 'max_guests' => 4, 'amenities' => ['wifi', 'ascenseur', 'chauffage']],
                ['title' => 'Loft avec terrasse', 'city' => 'Dubai', 'country' => 'AE', 'price_per_night' => 120000, 'bedrooms' => 2, 'bathrooms' => 2, 'max_guests' => 4, 'amenities' => ['wifi', 'piscine', 'climatisation', 'salle de sport']],
                ['title' => 'Residence meublee proche aeroport', 'city' => 'Douala', 'country' => 'CM', 'price_per_night' => 28000, 'bedrooms' => 1, 'bathrooms' => 1, 'max_guests' => 2, 'amenities' => ['wifi', 'parking']],
            ];

            foreach ($listings as $i => $l) {
                DB::table('lodging_listings')->insert([
                    'owner_id' => null,
                    'title' => $l['title'],
                    'slug' => Str::slug($l['title']).'-'.($i + 1),
                    'description' => 'Un hebergement confortable et bien situe a '.$l['city'].', ideal pour un sejour d\'affaires ou de loisirs.',
                    'city' => $l['city'],
                    'country' => $l['country'],
                    'address' => null,
                    'price_per_night' => $l['price_per_night'],
                    'currency' => 'XAF',
                    'bedrooms' => $l['bedrooms'],
                    'bathrooms' => $l['bathrooms'],
                    'max_guests' => $l['max_guests'],
                    'amenities' => json_encode($l['amenities']),
                    'image' => null,
                    'is_active' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('vehicles')->count() === 0) {
            $vehicles = [
                ['title' => 'Toyota Corolla 2022', 'brand' => 'Toyota', 'model' => 'Corolla', 'year' => 2022, 'category' => 'berline', 'transmission' => 'automatique', 'seats' => 5, 'price_per_day' => 25000, 'city' => 'Douala', 'country' => 'CM'],
                ['title' => 'Hyundai Tucson 2023', 'brand' => 'Hyundai', 'model' => 'Tucson', 'year' => 2023, 'category' => 'suv', 'transmission' => 'automatique', 'seats' => 5, 'price_per_day' => 40000, 'city' => 'Yaounde', 'country' => 'CM'],
                ['title' => 'Kia Picanto 2021', 'brand' => 'Kia', 'model' => 'Picanto', 'year' => 2021, 'category' => 'citadine', 'transmission' => 'manuelle', 'seats' => 4, 'price_per_day' => 15000, 'city' => 'Kinshasa', 'country' => 'CD'],
                ['title' => 'Toyota Hilux 2022', 'brand' => 'Toyota', 'model' => 'Hilux', 'year' => 2022, 'category' => 'utilitaire', 'transmission' => 'manuelle', 'seats' => 5, 'price_per_day' => 45000, 'city' => 'Libreville', 'country' => 'GA'],
                ['title' => 'Mercedes Classe C 2023', 'brand' => 'Mercedes-Benz', 'model' => 'Classe C', 'year' => 2023, 'category' => 'luxe', 'transmission' => 'automatique', 'seats' => 5, 'price_per_day' => 80000, 'city' => 'Douala', 'country' => 'CM'],
                ['title' => 'Renault Duster 2021', 'brand' => 'Renault', 'model' => 'Duster', 'year' => 2021, 'category' => 'suv', 'transmission' => 'manuelle', 'seats' => 5, 'price_per_day' => 30000, 'city' => 'Brazzaville', 'country' => 'CG'],
                ['title' => 'Volkswagen Golf 2020', 'brand' => 'Volkswagen', 'model' => 'Golf', 'year' => 2020, 'category' => 'berline', 'transmission' => 'manuelle', 'seats' => 5, 'price_per_day' => 22000, 'city' => 'Paris', 'country' => 'FR'],
            ];

            foreach ($vehicles as $i => $v) {
                DB::table('vehicles')->insert([
                    'owner_id' => null,
                    'title' => $v['title'],
                    'slug' => Str::slug($v['title']).'-'.($i + 1),
                    'brand' => $v['brand'],
                    'model' => $v['model'],
                    'year' => $v['year'],
                    'category' => $v['category'],
                    'transmission' => $v['transmission'],
                    'seats' => $v['seats'],
                    'price_per_day' => $v['price_per_day'],
                    'currency' => 'XAF',
                    'city' => $v['city'],
                    'country' => $v['country'],
                    'image' => null,
                    'is_active' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('events')->count() === 0) {
            $events = [
                ['title' => 'Concert Fally Ipupa Live', 'category' => 'concert', 'venue' => 'Palais des Sports', 'city' => 'Kinshasa', 'country' => 'CD', 'days' => 12, 'price' => 15000, 'capacity' => 3000],
                ['title' => 'Match Lions Indomptables vs Ghana', 'category' => 'sport', 'venue' => 'Stade Ahmadou Ahidjo', 'city' => 'Yaounde', 'country' => 'CM', 'days' => 20, 'price' => 8000, 'capacity' => 40000],
                ['title' => 'Festival Amani', 'category' => 'concert', 'venue' => 'Institut Francais', 'city' => 'Goma', 'country' => 'CD', 'days' => 35, 'price' => 10000, 'capacity' => 5000],
                ['title' => 'Spectacle humour - Le Grand Bal', 'category' => 'spectacle', 'venue' => 'Canal Olympia', 'city' => 'Douala', 'country' => 'CM', 'days' => 8, 'price' => 5000, 'capacity' => 800],
                ['title' => 'Avant-premiere cinema - Wakanda 3', 'category' => 'cinema', 'venue' => 'Cine Sita', 'city' => 'Libreville', 'country' => 'GA', 'days' => 15, 'price' => 4000, 'capacity' => 300],
                ['title' => 'Nuit electro Douala', 'category' => 'concert', 'venue' => 'La Falaise', 'city' => 'Douala', 'country' => 'CM', 'days' => 25, 'price' => 12000, 'capacity' => 1500],
            ];

            foreach ($events as $i => $e) {
                $startsAt = $now->copy()->addDays($e['days'])->setTime(19, 0);
                DB::table('events')->insert([
                    'organizer_id' => null,
                    'title' => $e['title'],
                    'slug' => Str::slug($e['title']).'-'.($i + 1),
                    'category' => $e['category'],
                    'description' => 'Ne manquez pas cet evenement a '.$e['venue'].', '.$e['city'].'.',
                    'venue' => $e['venue'],
                    'city' => $e['city'],
                    'country' => $e['country'],
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHours(3),
                    'price' => $e['price'],
                    'currency' => 'XAF',
                    'capacity' => $e['capacity'],
                    'image' => null,
                    'is_active' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('marketplace_products')->count() === 0) {
            $products = [
                ['title' => 'Smartphone Samsung Galaxy A54', 'category' => 'Electronique', 'price' => 210000, 'stock' => 15, 'condition' => 'neuf', 'city' => 'Douala'],
                ['title' => 'Ordinateur portable HP Pavilion 15', 'category' => 'Electronique', 'price' => 380000, 'stock' => 8, 'condition' => 'neuf', 'city' => 'Yaounde'],
                ['title' => 'Chaussures Nike Air Max (occasion, bon etat)', 'category' => 'Mode', 'price' => 25000, 'stock' => 5, 'condition' => 'occasion', 'city' => 'Kinshasa'],
                ['title' => 'Refrigerateur Samsung 350L', 'category' => 'Electromenager', 'price' => 250000, 'stock' => 4, 'condition' => 'neuf', 'city' => 'Libreville'],
                ['title' => 'Sac a main en cuir', 'category' => 'Mode', 'price' => 18000, 'stock' => 20, 'condition' => 'neuf', 'city' => 'Douala'],
                ['title' => 'Television LED 43 pouces', 'category' => 'Electronique', 'price' => 165000, 'stock' => 10, 'condition' => 'neuf', 'city' => 'Brazzaville'],
                ['title' => 'Velo VTT tout terrain', 'category' => 'Sport', 'price' => 95000, 'stock' => 6, 'condition' => 'neuf', 'city' => 'Douala'],
                ['title' => 'Canape 3 places (occasion)', 'category' => 'Maison', 'price' => 60000, 'stock' => 2, 'condition' => 'occasion', 'city' => 'Yaounde'],
                ['title' => 'Console PlayStation 5', 'category' => 'Electronique', 'price' => 420000, 'stock' => 3, 'condition' => 'neuf', 'city' => 'Kinshasa'],
                ['title' => 'Montre connectee sport', 'category' => 'Electronique', 'price' => 35000, 'stock' => 25, 'condition' => 'neuf', 'city' => 'Douala'],
            ];

            foreach ($products as $i => $p) {
                DB::table('marketplace_products')->insert([
                    'seller_id' => null,
                    'title' => $p['title'],
                    'slug' => Str::slug($p['title']).'-'.($i + 1),
                    'description' => 'Article disponible a '.$p['city'].'. Livraison possible via le module Fret.',
                    'category' => $p['category'],
                    'price' => $p['price'],
                    'currency' => 'XAF',
                    'stock' => $p['stock'],
                    'condition' => $p['condition'],
                    'city' => $p['city'],
                    'country' => 'CM',
                    'image' => null,
                    'is_active' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('freight_offers')->count() === 0) {
            $offers = [
                ['title' => 'Fret aerien express Douala - Paris', 'mode' => 'air', 'origin_city' => 'Douala', 'origin_country' => 'CM', 'destination_city' => 'Paris', 'destination_country' => 'FR', 'price_per_kg' => 8500, 'capacity_kg' => 500],
                ['title' => 'Fret maritime Douala - Marseille', 'mode' => 'mer', 'origin_city' => 'Douala', 'origin_country' => 'CM', 'destination_city' => 'Marseille', 'destination_country' => 'FR', 'price_per_kg' => 1200, 'capacity_kg' => 20000],
                ['title' => 'Transport routier Douala - Yaounde', 'mode' => 'route', 'origin_city' => 'Douala', 'origin_country' => 'CM', 'destination_city' => 'Yaounde', 'destination_country' => 'CM', 'price_per_kg' => 300, 'capacity_kg' => 3000],
                ['title' => 'Fret aerien Kinshasa - Bruxelles', 'mode' => 'air', 'origin_city' => 'Kinshasa', 'origin_country' => 'CD', 'destination_city' => 'Bruxelles', 'destination_country' => 'BE', 'price_per_kg' => 9200, 'capacity_kg' => 400],
                ['title' => 'Transport routier Libreville - Franceville', 'mode' => 'route', 'origin_city' => 'Libreville', 'origin_country' => 'GA', 'destination_city' => 'Franceville', 'destination_country' => 'GA', 'price_per_kg' => 450, 'capacity_kg' => 2000],
                ['title' => 'Fret maritime Pointe-Noire - Anvers', 'mode' => 'mer', 'origin_city' => 'Pointe-Noire', 'origin_country' => 'CG', 'destination_city' => 'Anvers', 'destination_country' => 'BE', 'price_per_kg' => 1350, 'capacity_kg' => 15000],
            ];

            foreach ($offers as $i => $o) {
                DB::table('freight_offers')->insert([
                    'carrier_id' => null,
                    'title' => $o['title'],
                    'slug' => Str::slug($o['title']).'-'.($i + 1),
                    'description' => 'Transport '.$o['mode'].' fiable entre '.$o['origin_city'].' et '.$o['destination_city'].'.',
                    'mode' => $o['mode'],
                    'origin_city' => $o['origin_city'],
                    'origin_country' => $o['origin_country'],
                    'destination_city' => $o['destination_city'],
                    'destination_country' => $o['destination_country'],
                    'price_per_kg' => $o['price_per_kg'],
                    'currency' => 'XAF',
                    'capacity_kg' => $o['capacity_kg'],
                    'image' => null,
                    'is_active' => true,
                    'status' => 'published',
                    'rejection_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('freight_shipments')->count() === 0) {
            // A tracking demo needs an owning user - reuse the first
            // existing account if there is one, otherwise create a
            // dedicated (harmless) demo account just for this data.
            $userId = DB::table('users')->orderBy('id')->value('id');

            if (! $userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => 'Demo Client',
                    'email' => 'demo@mokili-tour.com',
                    'password' => Hash::make('password'),
                    'role' => 'client',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $shipments = [
                ['code' => 'MKT-FRT-DEMO01', 'origin_city' => 'Douala', 'origin_country' => 'CM', 'destination_city' => 'Paris', 'destination_country' => 'FR', 'weight_kg' => 12.5, 'mode' => 'air', 'status' => 'en_transit'],
                ['code' => 'MKT-FRT-DEMO02', 'origin_city' => 'Yaounde', 'origin_country' => 'CM', 'destination_city' => 'Douala', 'destination_country' => 'CM', 'weight_kg' => 4, 'mode' => 'route', 'status' => 'livre'],
                ['code' => 'MKT-FRT-DEMO03', 'origin_city' => 'Kinshasa', 'origin_country' => 'CD', 'destination_city' => 'Bruxelles', 'destination_country' => 'BE', 'weight_kg' => 30, 'mode' => 'air', 'status' => 'dedouanement'],
                ['code' => 'MKT-FRT-DEMO04', 'origin_city' => 'Douala', 'origin_country' => 'CM', 'destination_city' => 'Marseille', 'destination_country' => 'FR', 'weight_kg' => 500, 'mode' => 'mer', 'status' => 'enregistre'],
            ];

            foreach ($shipments as $s) {
                DB::table('freight_shipments')->insert([
                    'user_id' => $userId,
                    'tracking_code' => $s['code'],
                    'origin_city' => $s['origin_city'],
                    'origin_country' => $s['origin_country'],
                    'destination_city' => $s['destination_city'],
                    'destination_country' => $s['destination_country'],
                    'weight_kg' => $s['weight_kg'],
                    'dimensions' => null,
                    'mode' => $s['mode'],
                    'status' => $s['status'],
                    'estimated_price' => null,
                    'currency' => 'XAF',
                    'picked_up_at' => in_array($s['status'], ['en_transit', 'dedouanement', 'livre'], true) ? $now->copy()->subDays(3) : null,
                    'delivered_at' => $s['status'] === 'livre' ? $now->copy()->subDay() : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration only seeds demo rows and
        // shouldn't destroy real partner data added afterwards.
    }
};
