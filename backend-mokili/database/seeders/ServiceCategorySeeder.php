<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

// The 6 pillars shown on the marketing artwork.
class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'voyage', 'name' => 'Voyage', 'icon' => 'plane', 'color' => '#0B2A5B', 'position' => 1],
            ['slug' => 'logement', 'name' => 'Logement', 'icon' => 'building-2', 'color' => '#1E8A3E', 'position' => 2],
            ['slug' => 'voiture', 'name' => 'Voiture', 'icon' => 'car', 'color' => '#E06A1D', 'position' => 3],
            ['slug' => 'divertissement', 'name' => 'Divertissement', 'icon' => 'ticket', 'color' => '#7A2FBF', 'position' => 4],
            ['slug' => 'marketplace', 'name' => 'Marketplace', 'icon' => 'shopping-bag', 'color' => '#D6216B', 'position' => 5],
            ['slug' => 'fret', 'name' => 'Fret', 'icon' => 'truck', 'color' => '#0FA3A3', 'position' => 6],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
