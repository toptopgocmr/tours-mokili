<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The 6 pillars of the platform, as shown on the marketing artwork:
// Voyage, Logement, Voiture, Divertissement, Marketplace, Fret.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // voyage, logement, voiture, divertissement, marketplace, fret
            $table->string('name');
            $table->string('icon')->nullable(); // lucide/heroicon name used by web + mobile
            $table->string('color')->nullable(); // brand accent color per service
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
