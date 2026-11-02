<?php

use Database\Seeders\AttractionParkSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Migration "de donnees" : en production (Railway) seules les migrations
// tournent au deploiement, pas les seeders. On ajoute donc ici la 7e
// categorie de service "Loisirs" (tuile page d'accueil web + mobile) et le
// catalogue de depart des parcs du Congo, une seule fois.
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (! DB::table('service_categories')->where('slug', 'loisirs')->exists()) {
            DB::table('service_categories')->insert([
                'slug' => 'loisirs',
                'name' => 'Loisirs & Parcs',
                'icon' => 'ferris-wheel',
                'color' => '#F59E0B',
                'description' => "Parcs d'attractions, parcs aquatiques, zoos et sites naturels du Congo.",
                'is_active' => true,
                'position' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        (new AttractionParkSeeder)->run();
    }

    public function down(): void
    {
        DB::table('service_categories')->where('slug', 'loisirs')->delete();
    }
};
