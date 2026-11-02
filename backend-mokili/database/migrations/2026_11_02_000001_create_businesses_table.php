<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GUIDE CONGO - annuaire des societes utiles au voyageur : hotels,
// compagnies aeriennes, transport routier, taxis/VTC, canots rapides,
// supermarches, restaurants & bars, boutiques en ligne.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category'); // hotel, compagnie_aerienne, transport_routier, taxi_vtc, canot_rapide, supermarche, restaurant, bar_lounge, ecommerce
            $table->string('city')->default('Brazzaville');
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('country', 2)->default('CG');
            $table->unsignedTinyInteger('stars')->nullable(); // hotels
            $table->text('description')->nullable();
            $table->json('services')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('price_range')->nullable(); // ex: "A partir de 12 000 FCFA"
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image')->nullable();   // fichier uploade, chemin /images/... ou URL https://
            $table->json('gallery')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
