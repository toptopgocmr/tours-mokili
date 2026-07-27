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
        ]);

        User::factory()->create([
            'name' => 'Demo Client',
            'email' => 'demo@mokili-tour.com',
            'password' => bcrypt('password'),
            'role' => 'client',
        ]);

        User::factory()->create([
            'name' => 'Admin MOKILI TOUR',
            'email' => 'admin@mokili-tour.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Agent MOKILI TOUR',
            'email' => 'agent@mokili-tour.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        User::factory()->create([
            'name' => 'Partenaire Demo',
            'email' => 'partner@mokili-tour.com',
            'password' => bcrypt('password'),
            'role' => 'partner',
        ]);
    }
}
