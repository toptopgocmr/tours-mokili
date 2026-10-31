<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Decaissements (PAY-OUT) Mobile Money : remboursement d'un client,
// reversement a un partenaire, paiement fournisseur... declenches depuis
// le back-office via Peex, pawaPay, MaishaPay ou Digitwace.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('gateway', 32);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();      // beneficiaire (si compte MOKILI)
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();   // ex. remboursement
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('beneficiary_name');
            $table->string('phone', 32);
            $table->string('country_code', 2);
            $table->string('network', 32)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processing, paid, failed
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
