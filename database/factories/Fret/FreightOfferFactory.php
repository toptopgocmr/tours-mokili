<?php

namespace Database\Factories\Fret;

use App\Models\Fret\FreightOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreightOfferFactory extends Factory
{
    protected $model = FreightOffer::class;

    public function definition(): array
    {
        $routes = [
            ['origin' => 'Kinshasa', 'originCountry' => 'CD', 'destination' => 'Lubumbashi', 'destinationCountry' => 'CD', 'mode' => 'air'],
            ['origin' => 'Kinshasa', 'originCountry' => 'CD', 'destination' => 'Guangzhou', 'destinationCountry' => 'CN', 'mode' => 'mer'],
            ['origin' => 'Douala', 'originCountry' => 'CM', 'destination' => 'Kinshasa', 'destinationCountry' => 'CD', 'mode' => 'route'],
            ['origin' => 'Kinshasa', 'originCountry' => 'CD', 'destination' => 'Dubai', 'destinationCountry' => 'AE', 'mode' => 'air'],
            ['origin' => 'Kinshasa', 'originCountry' => 'CD', 'destination' => 'Goma', 'destinationCountry' => 'CD', 'mode' => 'route'],
        ];
        $route = $this->faker->randomElement($routes);

        return [
            'title' => ucfirst($route['mode']).' '.$route['origin'].' - '.$route['destination'],
            'description' => $this->faker->paragraph(),
            'mode' => $route['mode'],
            'origin_city' => $route['origin'],
            'origin_country' => $route['originCountry'],
            'destination_city' => $route['destination'],
            'destination_country' => $route['destinationCountry'],
            'price_per_kg' => $this->faker->numberBetween(2, 15) * 1000,
            'currency' => 'XAF',
            'capacity_kg' => $this->faker->numberBetween(500, 5000),
            'image' => null,
            'is_active' => true,
            'status' => 'published',
        ];
    }
}
