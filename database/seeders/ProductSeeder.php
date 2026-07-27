<?php

namespace Database\Seeders;

use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sellerId = User::where('email', 'partner@mokili-tour.com')->value('id');

        Product::factory()
            ->count(12)
            ->create(['seller_id' => $sellerId]);
    }
}
