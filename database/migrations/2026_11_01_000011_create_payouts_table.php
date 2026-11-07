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
        // Deploiement precedent interrompu (MySQL ne fait pas de rollback des
        // CREATE TABLE) : table presente sans trace dans `migrations`.
        if (Schema::hasTable('payouts')) {
            if (\Illuminate\Support\Facades\DB::table('payouts')->exists()) {
                return; // deja creee et utilisee : on la garde
            }
            Schema::drop('payouts');
        }

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('gateway', 32);
            $table->foreignId('user_id')->nullable()->index();      // beneficiaire (si compte MOKILI)
            $table->foreignId('booking_id')->nullable()->index();   // ex. remboursement
            $table->foreignId('payment_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
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
