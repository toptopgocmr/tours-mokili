<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Trajets bus / train / canot facon BlaBlaCar : gare d'arrivee et services a bord. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_offers', function (Blueprint $t) {
            if (! Schema::hasColumn('travel_offers', 'arrival_point')) {
                $t->string('arrival_point')->nullable();
            }
            if (! Schema::hasColumn('travel_offers', 'amenities')) {
                $t->json('amenities')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_offers', function (Blueprint $t) {
            foreach (['arrival_point', 'amenities'] as $c) {
                if (Schema::hasColumn('travel_offers', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
