<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FRET module - partner-published freight/shipping service listings
// (distinct from `freight_shipments`, which tracks a customer's actual
// shipment once booked/created).
return new class extends Migration
{
    public function up(): void
    {
        // Guarded with hasTable(): a prior deploy could have been killed
        // (e.g. by the platform's health-check timeout) after the CREATE
        // TABLE succeeded but before Laravel recorded this migration as
        // run, which made every subsequent boot re-attempt the create and
        // crash with SQLSTATE[42S01] "table already exists", looping the
        // deploy forever. This makes the migration idempotent/safe to
        // re-run in that scenario.
        if (Schema::hasTable('freight_offers')) {
            return;
        }

        Schema::create('freight_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('mode', ['air', 'mer', 'route'])->default('route');
            $table->string('origin_city');
            $table->string('origin_country', 2);
            $table->string('destination_city');
            $table->string('destination_country', 2);
            $table->decimal('price_per_kg', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_offers');
    }
};
