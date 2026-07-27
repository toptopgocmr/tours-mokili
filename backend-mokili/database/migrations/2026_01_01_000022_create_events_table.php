<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// DIVERTISSEMENT module (skeleton) - concerts, matchs, spectacles, billetterie.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable(); // concert, sport, spectacle, cinema...
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedInteger('capacity')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
