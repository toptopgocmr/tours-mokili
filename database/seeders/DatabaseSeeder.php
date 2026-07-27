<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users first: the partner-owned catalogue seeders below (Logement,
        // Voiture, Divertissement, Marketplace, Fret) attach their listings
        // to "Partenaire Demo", so its id must already exist.
        User::firstOrCreate(
            ['email' => 'demo@mokili-tour.com'],
            ['name' => 'Demo Client', 'password' => bcrypt('password'), 'role' => 'client']
        );

        User::firstOrCreate(
            ['email' => 'admin@mokili-tour.com'],
            ['name' => 'Admin MOKILI TOUR', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        User::firstOrCreate(
            ['email' => 'agent@mokili-tour.com'],
            ['name' => 'Agent MOKILI TOUR', 'password' => bcrypt('password'), 'role' => 'agent']
        );

        User::firstOrCreate(
            ['email' => 'partner@mokili-tour.com'],
            ['name' => 'Partenaire Demo', 'password' => bcrypt('password'), 'role' => 'partner']
        );

        $this->call([
            ServiceCategorySeeder::class,
            TravelOfferSeeder::class,

            // LOGEMENT / VOITURE / DIVERTISSEMENT / MARKETPLACE / FRET now
            // have exposed API routes (see routes/api.php) - seed each with
            // published demo listings so the mobile + web catalogues aren't
            // empty out of the box.
            LodgingListingSeeder::class,
            VehicleSeeder::class,
            EventSeeder::class,
            ProductSeeder::class,
            FreightOfferSeeder::class,
            ShipmentSeeder::class,
        ]);
    }
}
