<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voiture > Vente : plus de reservation ni de paiement en ligne. Le client
 * prend rendez-vous pour voir / essayer / acheter le vehicule et paie sur
 * place. Les rendez-vous sont des "visit_requests" liees a un vehicule
 * (business_id devient facultatif).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visit_requests')) {
            return;
        }
        Schema::table('visit_requests', function (Blueprint $t) {
            $t->unsignedBigInteger('business_id')->nullable()->change();
            if (! Schema::hasColumn('visit_requests', 'vehicle_id')) {
                $t->foreignId('vehicle_id')->nullable()->after('business_id')->constrained('vehicles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('visit_requests') && Schema::hasColumn('visit_requests', 'vehicle_id')) {
            Schema::table('visit_requests', function (Blueprint $t) {
                $t->dropConstrainedForeignId('vehicle_id');
            });
        }
    }
};
