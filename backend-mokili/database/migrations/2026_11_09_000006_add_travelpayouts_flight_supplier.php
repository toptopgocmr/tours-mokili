<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fournisseur TRAVELPAYOUTS / Aviasales (prix reels indicatifs, gratuit).
 * Actif, mais interroge seulement quand TRAVELPAYOUTS_TOKEN est renseigne.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('flight_suppliers')->updateOrInsert(['code' => 'travelpayouts'], [
            'name' => 'Aviasales (Travelpayouts)',
            'driver' => 'travelpayouts', 'is_active' => true, 'priority' => 7,
            'connection_status' => 'unknown', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('flight_suppliers')->where('code', 'travelpayouts')->delete();
    }
};
