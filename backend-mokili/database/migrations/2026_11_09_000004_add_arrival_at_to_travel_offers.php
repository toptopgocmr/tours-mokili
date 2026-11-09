<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Trajets bus / train / taxi (facon BlaBlaCar) : heure d'arrivee prevue. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('travel_offers', 'arrival_at')) {
            Schema::table('travel_offers', function (Blueprint $table) {
                $table->dateTime('arrival_at')->nullable()->after('departure_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('travel_offers', 'arrival_at')) {
            Schema::table('travel_offers', fn (Blueprint $table) => $table->dropColumn('arrival_at'));
        }
    }
};
