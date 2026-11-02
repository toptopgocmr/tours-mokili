<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceCategorySeeder::class,
            TravelOfferSeeder::class,
            // Parcs d'attractions du Congo + societes partenaires par rubrique.
            AttractionParkSeeder::class,
            BusinessSeeder::class,
        ]);

        // firstOrCreate : le seeder peut etre relance sans doublon.
        User::query()->firstOrCreate(['email' => 'demo@mokili-tour.com'], [
            'name' => 'Demo Client',
            'password' => bcrypt('password'),
            'role' => 'client',
        ]);

        User::query()->firstOrCreate(['email' => 'admin@mokili-tour.com'], [
            'name' => 'Admin MOKILI TOUR',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::query()->firstOrCreate(['email' => 'agent@mokili-tour.com'], [
            'name' => 'Agent MOKILI TOUR',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        User::query()->firstOrCreate(['email' => 'partner@mokili-tour.com'], [
            'name' => 'Partenaire Demo',
            'password' => bcrypt('password'),
            'role' => 'partner',
        ]);
    }
}
