<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MARKETPLACE module (skeleton) - biens et services entre particuliers/pros.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedInteger('stock')->default(1);
            $table->enum('condition', ['neuf', 'occasion'])->default('neuf');
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
