<?php

namespace Database\Seeders;

use App\Models\Logement\LodgingListing;
use App\Models\User;
use Illuminate\Database\Seeder;

class LodgingListingSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::where('email', 'partner@mokili-tour.com')->value('id');

        LodgingListing::factory()
            ->count(10)
            ->create(['owner_id' => $ownerId]);
    }
}
