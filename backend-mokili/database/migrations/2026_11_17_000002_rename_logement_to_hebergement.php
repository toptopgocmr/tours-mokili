<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// La rubrique "Logement" s'appelle desormais "Hébergement" (site et app).
// Le slug "logement" et les adresses /logement ne changent pas.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_categories')->where('slug', 'logement')->update(['name' => 'Hébergement', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('service_categories')->where('slug', 'logement')->update(['name' => 'Logement', 'updated_at' => now()]);
    }
};
