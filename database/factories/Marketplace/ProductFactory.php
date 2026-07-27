<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $products = [
            ['title' => 'Smartphone Samsung Galaxy A15', 'category' => 'electronique', 'price' => [150, 350]],
            ['title' => 'Groupe electrogene 5kVA', 'category' => 'maison', 'price' => [400, 900]],
            ['title' => 'Sac de riz 25kg', 'category' => 'alimentation', 'price' => [15, 35]],
            ['title' => 'Chaussures de sport', 'category' => 'mode', 'price' => [20, 60]],
            ['title' => 'Television LED 43"', 'category' => 'electronique', 'price' => [200, 450]],
            ['title' => 'Climatiseur split 1.5CV', 'category' => 'maison', 'price' => [300, 600]],
        ];
        $pick = $this->faker->randomElement($products);

        return [
            'title' => $pick['title'],
            'description' => $this->faker->paragraph(),
            'category' => $pick['category'],
            'price' => $this->faker->numberBetween(...$pick['price']) * 1000,
            'currency' => 'XAF',
            'stock' => $this->faker->numberBetween(1, 50),
            'condition' => $this->faker->randomElement(['neuf', 'neuf', 'occasion']),
            'city' => $this->faker->randomElement(['Kinshasa', 'Lubumbashi', 'Goma', 'Douala']),
            'country' => 'CD',
            'image' => null,
            'is_active' => true,
            'status' => 'published',
        ];
    }
}
