<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fournisseur DUFFEL (vols en direct, inscription gratuite). Actif, mais
 * interroge seulement quand DUFFEL_ACCESS_TOKEN est renseigne sur Railway.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('flight_suppliers')->updateOrInsert(['code' => 'duffel'], [
            'name' => 'Duffel (vols en direct)',
            'driver' => 'duffel', 'is_active' => true, 'priority' => 6,
            'connection_status' => 'unknown', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('flight_suppliers')->where('code', 'duffel')->delete();
    }
};
