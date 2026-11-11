<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les colonnes "image" etaient des VARCHAR(191) (Schema::defaultStringLength).
 * Les URL Wikimedia du catalogue de vente (000008) depassent 300 caracteres :
 * MySQL refusait l'insertion ("Data too long for column 'image'"), le
 * conteneur Railway redemarrait en boucle et le healthcheck echouait.
 * Les colonnes deviennent des TEXT. Doit passer avant 000008.
 */
return new class extends Migration
{
    private const TABLES = [
        'vehicles', 'travel_offers', 'lodging_listings', 'events', 'freight_offers',
        'marketplace_products', 'attraction_parks', 'businesses',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'image')) {
                Schema::table($table, fn (Blueprint $t) => $t->text('image')->nullable()->change());
            }
        }
    }

    public function down(): void
    {
        // Retour en VARCHAR(191) impossible sans tronquer des URL : on garde TEXT.
    }
};
