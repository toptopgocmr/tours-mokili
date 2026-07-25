<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FRET module (skeleton) - expedition de colis / marchandises.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freight_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_code')->unique();
            $table->string('origin_city');
            $table->string('origin_country', 2);
            $table->string('destination_city');
            $table->string('destination_country', 2);
            $table->decimal('weight_kg', 8, 2)->default(1);
            $table->string('dimensions')->nullable(); // "LxWxH cm"
            $table->enum('mode', ['air', 'mer', 'route'])->default('air');
            $table->enum('status', ['enregistre', 'en_transit', 'dedouanement', 'livre', 'annule'])
                ->default('enregistre');
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_shipments');
    }
};
