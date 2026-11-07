<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('flight_searches')) {
            return;
        }

        Schema::table('flight_searches', function (Blueprint $table) {
            if (! Schema::hasColumn('flight_searches', 'legs')) {
                $table->json('legs')->nullable()->after('return_date');          // multi-destinations : [{origin,destination,date}]
            }
            if (! Schema::hasColumn('flight_searches', 'direct_only')) {
                $table->boolean('direct_only')->default(false)->after('cabin');  // vols directs uniquement
            }
        });
    }

    public function down(): void
    {
        Schema::table('flight_searches', function (Blueprint $table) {
            $table->dropColumn(['legs', 'direct_only']);
        });
    }
};
