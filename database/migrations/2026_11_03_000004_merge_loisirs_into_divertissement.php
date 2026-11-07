<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Les parcs d'attractions passent dans la rubrique Divertissement : on
// retire la tuile "Loisirs & Parcs" (web + app) sans toucher aux parcs.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_categories')->where('slug', 'loisirs')->delete();

        DB::table('service_categories')->where('slug', 'divertissement')->update([
            'description' => "Concerts, spectacles, sport, sorties, parcs d'attractions et loisirs.",
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! DB::table('service_categories')->where('slug', 'loisirs')->exists()) {
            DB::table('service_categories')->insert([
                'slug' => 'loisirs', 'name' => 'Loisirs & Parcs', 'icon' => 'ferris-wheel',
                'color' => '#F59E0B', 'is_active' => true, 'position' => 7,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
};
