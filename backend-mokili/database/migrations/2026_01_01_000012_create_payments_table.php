<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Payment attempts settled through Peex (Collect/Disbursement APIs),
// preceded by a wallet verification (see peex_verifications table).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('peex');
            $table->enum('method', ['mobile_money', 'bank'])->default('mobile_money');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->string('peex_track_id')->nullable();
            $table->string('peex_request_id')->nullable();
            $table->json('peex_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
