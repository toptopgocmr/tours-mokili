<?php

namespace Database\Factories\Voiture;

use App\Models\Voiture\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $cities = ['Kinshasa', 'Lubumbashi', 'Douala', 'Libreville', 'Brazzaville'];
        $models = [
            ['brand' => 'Toyota', 'model' => 'Corolla', 'category' => 'berline'],
            ['brand' => 'Toyota', 'model' => 'Land Cruiser', 'category' => 'suv'],
            ['brand' => 'Hyundai', 'model' => 'Tucson', 'category' => 'suv'],
            ['brand' => 'Kia', 'model' => 'Picanto', 'category' => 'citadine'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Classe E', 'category' => 'luxe'],
            ['brand' => 'Toyota', 'model' => 'Hiace', 'category' => 'utilitaire'],
        ];
        $pick = $this->faker->randomElement($models);

        return [
            'title' => $pick['brand'].' '.$pick['model'],
            'brand' => $pick['brand'],
            'model' => $pick['model'],
            'year' => $this->faker->numberBetween(2016, 2025),
            'category' => $pick['category'],
            'transmission' => $this->faker->randomElement(['manuelle', 'automatique']),
            'seats' => $pick['category'] === 'utilitaire' ? 2 : $this->faker->numberBetween(4, 7),
            'price_per_day' => $this->faker->numberBetween(30, 200) * 1000,
            'currency' => 'XAF',
            'city' => $this->faker->randomElement($cities),
            'country' => 'CD',
            'image' => null,
            'is_active' => true,
            'status' => 'published',
        ];
    }
}
