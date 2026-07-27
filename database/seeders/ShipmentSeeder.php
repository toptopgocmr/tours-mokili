<?php

namespace Database\Seeders;

use App\Models\Fret\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One ready-to-test shipment so "Suivre un colis" (mobile Fret tab,
 * see fret_screen.dart's "ex: MKT-FRT-DEMO01" hint) has something to
 * find out of the box, without needing to place a real order first.
 */
class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Shipment::where('tracking_code', 'MKT-FRT-DEMO01')->exists()) {
            return;
        }

        Shipment::create([
            'user_id' => User::where('email', 'demo@mokili-tour.com')->value('id'),
            'tracking_code' => 'MKT-FRT-DEMO01',
            'origin_city' => 'Kinshasa',
            'origin_country' => 'CD',
            'destination_city' => 'Lubumbashi',
            'destination_country' => 'CD',
            'weight_kg' => 12.5,
            'mode' => 'air',
            'status' => 'en_transit',
            'estimated_price' => 87500,
            'currency' => 'XAF',
            'picked_up_at' => now()->subDays(2),
        ]);
    }
}
