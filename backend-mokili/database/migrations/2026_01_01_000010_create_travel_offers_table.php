<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// VOYAGE module (pilot module - fully implemented).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['vol', 'sejour', 'circuit'])->default('vol');
            $table->text('description')->nullable();
            $table->string('origin_city')->nullable();
            $table->string('origin_country', 2)->nullable();
            $table->string('destination_city');
            $table->string('destination_country', 2);
            $table->string('airline')->nullable();
            $table->dateTime('departure_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedInteger('seats_available')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_offers');
    }
};
