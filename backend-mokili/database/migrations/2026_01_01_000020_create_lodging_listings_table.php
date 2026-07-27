<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LOGEMENT module (skeleton - ready for CRUD, mirrors Voyage structure).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lodging_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('city');
            $table->string('country', 2);
            $table->string('address')->nullable();
            $table->decimal('price_per_night', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->json('amenities')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lodging_listings');
    }
};
