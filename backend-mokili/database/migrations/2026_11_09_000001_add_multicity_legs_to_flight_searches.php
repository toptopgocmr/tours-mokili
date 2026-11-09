<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vols multi-destinations : liste des trajets (origine, destination, date)
 * pour trip_type = "multicity". origin / destination / depart_date gardent
 * le premier depart et la derniere arrivee.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('flight_searches', 'legs')) {
            Schema::table('flight_searches', function (Blueprint $table) {
                $table->json('legs')->nullable()->after('return_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('flight_searches', 'legs')) {
            Schema::table('flight_searches', function (Blueprint $table) {
                $table->dropColumn('legs');
            });
        }
    }
};
