<?php

namespace Database\Seeders;

use App\Models\Fret\FreightOffer;
use App\Models\User;
use Illuminate\Database\Seeder;

class FreightOfferSeeder extends Seeder
{
    public function run(): void
    {
        $carrierId = User::where('email', 'partner@mokili-tour.com')->value('id');

        FreightOffer::factory()
            ->count(8)
            ->create(['carrier_id' => $carrierId]);
    }
}
