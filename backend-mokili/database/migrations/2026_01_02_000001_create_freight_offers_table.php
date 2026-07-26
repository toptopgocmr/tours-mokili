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
