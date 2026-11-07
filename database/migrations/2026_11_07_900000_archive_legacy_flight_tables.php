<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * La premiere billetterie (tables flight_bookings, flight_fares...) a ete
 * remplacee par le moteur a commandes (flight_orders...). Les deux jeux de
 * tables portent des noms communs (airports, flight_offers...) : sur une base
 * qui a deja execute l'ancienne version, on archive les anciennes tables sous
 * le prefixe legacy_ avant de creer les nouvelles. Sans effet sur une base neuve.
 */
return new class extends Migration
{
    private const SHARED = [
        'flight_tickets', 'flight_passengers', 'flight_offers', 'flight_searches', 'flight_suppliers', 'airports',
    ];

    public function up(): void
    {
        $legacy = Schema::hasTable('flight_bookings') && ! Schema::hasTable('flight_orders');
        if (! $legacy) {
            return;
        }

        foreach (self::SHARED as $table) {
            if (Schema::hasTable($table) && ! Schema::hasTable('legacy_'.$table)) {
                Schema::rename($table, 'legacy_'.$table);
            }
        }
    }

    public function down(): void
    {
        // Archivage volontairement non reversible automatiquement.
    }
};
