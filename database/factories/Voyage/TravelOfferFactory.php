<?php

namespace Database\Factories\Voyage;

use App\Models\Voyage\TravelOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TravelOfferFactory extends Factory
{
    protected $model = TravelOffer::class;

    public function definition(): array
    {
        $destinations = [
            ['city' => 'Paris', 'country' => 'FR'],
            ['city' => 'Dubai', 'country' => 'AE'],
            ['city' => 'Chine', 'country' => 'CN'],
            ['city' => 'Istanbul', 'country' => 'TR'],
            ['city' => 'Libreville', 'country' => 'GA'],
            ['city' => 'Douala', 'country' => 'CM'],
            ['city' => 'Kinshasa', 'country' => 'CD'],
        ];

        $destination = $this->faker->randomElement($destinations);
        $departure = $this->faker->dateTimeBetween('+1 week', '+3 months');

        return [
            'title' => 'Sejour a '.$destination['city'],
            'type' => $this->faker->randomElement(['vol', 'sejour', 'circuit']),
            'description' => $this->faker->paragraph(),
            'origin_city' => 'Kinshasa',
            'origin_country' => 'CD',
            'destination_city' => $destination['city'],
            'destination_country' => $destination['country'],
            'airline' => $this->faker->randomElement(['Air France', 'Turkish Airlines', 'Ethiopian Airlines', 'RwandAir']),
            'departure_at' => $departure,
            'return_at' => (clone $departure)->modify('+'.$this->faker->numberBetween(3, 14).' days'),
            'price' => $this->faker->numberBetween(250, 1800) * 1000,
            'discount_percent' => $this->faker->randomElement([0, 0, 10, 15, 20]),
            'currency' => 'XAF',
            'seats_available' => $this->faker->numberBetween(0, 40),
            'image' => null,
            'is_featured' => false,
            'is_active' => true,
        ];
    }
}
