<?php

use Database\Seeders\BusinessSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Carnet de societes partenaires (hotels -> Logement, compagnies/bus/canots
// -> Voyage, taxis -> Voiture, restaurants/bars -> Divertissement,
// supermarches/e-commerce -> Marketplace). Migration de donnees : en
// production seules les migrations tournent au deploiement.
return new class extends Migration
{
    public function up(): void
    {
        (new BusinessSeeder)->run();
    }

    public function down(): void
    {
        DB::table('businesses')->truncate();
    }
};
