<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offre brute du fournisseur (necessaire pour re-pricer et reserver chez Amadeus). Jamais exposee.
        if (! Schema::hasColumn('flight_offers', 'raw')) {
            Schema::table('flight_offers', function (Blueprint $table) {
                $table->longText('raw')->nullable()->after('fare_rules');
            });
        }

        // Suivi de vol par segment (retards, annulations, changements).
        Schema::create('flight_segment_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->string('flight_number', 12);
            $table->date('flight_date');
            $table->string('origin', 3)->nullable();
            $table->string('destination', 3)->nullable();
            $table->string('status')->default('scheduled'); // scheduled, active, landed, delayed, cancelled, diverted, unknown
            $table->integer('delay_minutes')->default(0);
            $table->string('estimated_departure')->nullable();
            $table->string('estimated_arrival')->nullable();
            $table->string('provider')->nullable();
            $table->integer('notified_delay')->default(0);
            $table->string('notified_status')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['flight_order_id', 'flight_number', 'flight_date'], 'fss_unique');
        });

        // Fournisseur Amadeus : desactive tant que les cles API ne sont pas configurees
        // (activer avec : php artisan flights:supplier amadeus on).
        if (! DB::table('flight_suppliers')->where('code', 'amadeus')->exists()) {
            DB::table('flight_suppliers')->insert([
                'code' => 'amadeus', 'name' => 'Amadeus', 'driver' => 'amadeus',
                'is_active' => false, 'connection_status' => 'unknown',
                'settings' => json_encode(['note' => 'Cles AMADEUS_CLIENT_ID / AMADEUS_CLIENT_SECRET dans l\'environnement.']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_segment_statuses');
        if (Schema::hasColumn('flight_offers', 'raw')) {
            Schema::table('flight_offers', fn (Blueprint $t) => $t->dropColumn('raw'));
        }
    }
};
