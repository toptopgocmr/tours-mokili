<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Voiture\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::where('email', 'partner@mokili-tour.com')->value('id');

        Vehicle::factory()
            ->count(10)
            ->create(['owner_id' => $ownerId]);
    }
}
