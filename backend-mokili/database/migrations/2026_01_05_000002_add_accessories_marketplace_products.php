<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Adds an "Accessoires" category to the Marketplace demo catalogue -
// the original seed (2026_01_05_000001) only covered Electronique,
// Mode, Electromenager, Sport and Maison. Guarded so it only inserts
// once (won't duplicate rows or touch real partner products).
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('marketplace_products')->where('category', 'Accessoires')->exists()) {
            return;
        }

        $now = now();

        $products = [
            ['title' => 'Coque de protection iPhone 14', 'price' => 8000, 'stock' => 30, 'condition' => 'neuf', 'city' => 'Douala'],
            ['title' => 'Lunettes de soleil polarisees', 'price' => 15000, 'stock' => 18, 'condition' => 'neuf', 'city' => 'Yaounde'],
            ['title' => 'Montre bracelet cuir homme', 'price' => 22000, 'stock' => 12, 'condition' => 'neuf', 'city' => 'Kinshasa'],
            ['title' => 'Sac a dos pour ordinateur portable', 'price' => 27000, 'stock' => 10, 'condition' => 'neuf', 'city' => 'Douala'],
            ['title' => 'Ceinture en cuir veritable', 'price' => 9500, 'stock' => 20, 'condition' => 'neuf', 'city' => 'Libreville'],
            ['title' => 'Casquette brodee MOKILI', 'price' => 6000, 'stock' => 25, 'condition' => 'neuf', 'city' => 'Brazzaville'],
            ['title' => 'Chargeur rapide + cable USB-C', 'price' => 7500, 'stock' => 40, 'condition' => 'neuf', 'city' => 'Douala'],
            ['title' => 'Bracelet en perles fait main', 'price' => 4500, 'stock' => 22, 'condition' => 'neuf', 'city' => 'Kinshasa'],
        ];

        foreach ($products as $i => $p) {
            DB::table('marketplace_products')->insert([
                'seller_id' => null,
                'title' => $p['title'],
                'slug' => Str::slug($p['title']).'-'.($i + 1),
                'description' => 'Accessoire disponible a '.$p['city'].'. Livraison possible via le module Fret.',
                'category' => 'Accessoires',
                'price' => $p['price'],
                'currency' => 'XAF',
                'stock' => $p['stock'],
                'condition' => $p['condition'],
                'city' => $p['city'],
                'country' => 'CM',
                'image' => null,
                'is_active' => true,
                'status' => 'published',
                'rejection_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('marketplace_products')->where('category', 'Accessoires')->delete();
    }
};
