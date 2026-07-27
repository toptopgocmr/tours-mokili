<?php

namespace Database\Seeders;

use App\Models\Voyage\TravelOffer;
use Illuminate\Database\Seeder;

class TravelOfferSeeder extends Seeder
{
    public function run(): void
    {
        // The first 4 offers are "featured" (shown on the homepage), so we
        // force them to distinct destinations - otherwise the random
        // factory can (and did) pick Paris or Dubai twice.
        $featured = [
            ['city' => 'Paris', 'country' => 'FR'],
            ['city' => 'Dubai', 'country' => 'AE'],
            ['city' => 'Chine', 'country' => 'CN'],
            ['city' => 'Istanbul', 'country' => 'TR'],
        ];

        TravelOffer::factory()
            ->count(12)
            ->sequence(fn ($sequence) => $sequence->index < 4
                ? [
                    'is_featured' => true,
                    'title' => 'Sejour a '.$featured[$sequence->index]['city'],
                    'destination_city' => $featured[$sequence->index]['city'],
                    'destination_country' => $featured[$sequence->index]['country'],
                ]
                : ['is_featured' => false])
            ->create();
    }
}
