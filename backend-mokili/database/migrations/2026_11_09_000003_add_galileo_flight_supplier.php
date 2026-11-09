<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fournisseur Travelport GALILEO (1G). Actif, mais interroge seulement
 * quand les identifiants GALILEO_* sont renseignes sur Railway.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('flight_suppliers')->updateOrInsert(['code' => 'galileo'], [
            'name' => 'Travelport Galileo (GDS)',
            'driver' => 'galileo', 'is_active' => true, 'priority' => 5,
            'connection_status' => 'unknown', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('flight_suppliers')->where('code', 'galileo')->delete();
    }
};
