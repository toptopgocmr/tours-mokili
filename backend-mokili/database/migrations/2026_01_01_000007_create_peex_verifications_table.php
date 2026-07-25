<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Full audit trail of every call made to POST clients/verify-wallet,
// independent of the "current" state cached on the wallets table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peex_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->string('account_number');
            $table->boolean('is_valid')->default(false);
            $table->string('account_name')->nullable();
            $table->string('operator')->nullable();
            $table->string('status')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peex_verifications');
    }
};
