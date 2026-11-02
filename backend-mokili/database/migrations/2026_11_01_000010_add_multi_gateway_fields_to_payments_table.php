<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Multi-passerelles : un paiement peut maintenant passer par Peex, pawaPay,
// MaishaPay ou Digitwace (colonne "provider" existante = nom de la
// passerelle). On garde les colonnes peex_* pour l'historique.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('reference')->nullable()->unique()->after('id');
            $table->string('phone', 32)->nullable()->after('method');
            $table->string('country_code', 2)->nullable()->after('phone');
            $table->string('network', 32)->nullable()->after('country_code');
            $table->string('gateway_reference')->nullable()->after('peex_request_id');
            $table->json('gateway_response')->nullable()->after('gateway_reference');
            $table->string('failure_reason')->nullable()->after('gateway_response');
            $table->timestamp('last_checked_at')->nullable()->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'phone', 'country_code', 'network', 'gateway_reference', 'gateway_response', 'failure_reason', 'last_checked_at']);
        });
    }
};
