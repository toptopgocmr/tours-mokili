<?php

namespace Database\Factories\Logement;

use App\Models\Logement\LodgingListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class LodgingListingFactory extends Factory
{
    protected $model = LodgingListing::class;

    public function definition(): array
    {
        $places = [
            ['city' => 'Kinshasa', 'country' => 'CD'],
            ['city' => 'Lubumbashi', 'country' => 'CD'],
            ['city' => 'Goma', 'country' => 'CD'],
            ['city' => 'Douala', 'country' => 'CM'],
            ['city' => 'Libreville', 'country' => 'GA'],
            ['city' => 'Brazzaville', 'country' => 'CG'],
        ];
        $place = $this->faker->randomElement($places);
        $type = $this->faker->randomElement(['Appartement moderne', 'Villa avec piscine', 'Studio meuble', 'Maison familiale', 'Suite standing']);

        return [
            'title' => $type.' a '.$place['city'],
            'description' => $this->faker->paragraph(),
            'city' => $place['city'],
            'country' => $place['country'],
            'address' => $this->faker->streetAddress(),
            'price_per_night' => $this->faker->numberBetween(25, 250) * 1000,
            'currency' => 'XAF',
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'max_guests' => $this->faker->numberBetween(2, 10),
            'amenities' => $this->faker->randomElements(
                ['wifi', 'climatisation', 'piscine', 'parking', 'cuisine equipee', 'groupe electrogene'],
                $this->faker->numberBetween(2, 4)
            ),
            'image' => null,
            'is_active' => true,
            'status' => 'published',
        ];
    }
}
