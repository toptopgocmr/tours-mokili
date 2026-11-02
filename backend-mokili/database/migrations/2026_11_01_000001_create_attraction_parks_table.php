<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LOISIRS module - parcs d'attractions, parcs aquatiques, zoos, aires de
// jeux et parcs naturels du Congo-Brazzaville. Contrairement aux
// evenements (Divertissement), un parc est un lieu permanent : pas de date
// de debut obligatoire, des tarifs adulte / enfant et des horaires.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attraction_parks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // parc_attractions, parc_aquatique, zoo, aire_de_jeux, parc_naturel, ile_loisirs
            $table->string('category')->default('parc_attractions');
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('country', 2)->default('CG');
            $table->text('description')->nullable();
            $table->json('activities')->nullable();
            $table->string('opening_hours')->nullable();
            $table->decimal('price_adult', 12, 2)->nullable(); // null = tarif communique sur place
            $table->decimal('price_child', 12, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->string('min_age')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable(); // photos supplementaires (URL ou chemins)
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['city', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attraction_parks');
    }
};
