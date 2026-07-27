<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('country_code', 2)->nullable();
            $table->string('account_number')->nullable(); // mobile money / bank identifier verified via Peex
            $table->string('operator')->nullable(); // ORANGE, MTN, ... returned by Peex
            $table->string('account_name')->nullable(); // registered holder name returned by Peex
            $table->string('peex_status')->nullable(); // ACTIVE, ...
            $table->timestamp('peex_verified_at')->nullable();
            $table->decimal('balance', 14, 2)->default(0); // internal MOKILI TOUR balance/credit, informational
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
