<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// VOITURE module (skeleton).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('category', ['citadine', 'berline', 'suv', 'utilitaire', 'luxe'])->default('berline');
            $table->enum('transmission', ['manuelle', 'automatique'])->default('manuelle');
            $table->unsignedTinyInteger('seats')->default(4);
            $table->decimal('price_per_day', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
