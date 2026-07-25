<?php

namespace Database\Seeders;

use App\Models\Voyage\TravelOffer;
use Illuminate\Database\Seeder;

class TravelOfferSeeder extends Seeder
{
    public function run(): void
    {
        TravelOffer::factory()
            ->count(12)
            ->sequence(fn ($sequence) => ['is_featured' => $sequence->index < 4])
            ->create();
    }
}
