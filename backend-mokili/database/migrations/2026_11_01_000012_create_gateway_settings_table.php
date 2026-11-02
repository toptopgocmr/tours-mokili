<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Reglages des moyens de paiement modifiables depuis le back-office
// (/admin/passerelles) : activer/desactiver la collecte et le decaissement
// par passerelle, et choisir la passerelle preferee par pays. Les cles API
// restent dans les variables d'environnement.
return new class extends Migration
{
    public function up(): void
    {
        // Deploiement precedent interrompu (MySQL ne fait pas de rollback des
        // CREATE TABLE) : table presente sans trace dans `migrations`.
        if (Schema::hasTable('gateway_settings')) {
            if (\Illuminate\Support\Facades\DB::table('gateway_settings')->exists()) {
                return; // deja creee et utilisee : on la garde
            }
            Schema::drop('gateway_settings');
        }

        Schema::create('gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32)->unique();
            $table->boolean('collect_enabled')->default(true);
            $table->boolean('payout_enabled')->default(true);
            $table->json('preferred_countries')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['gateway' => 'peex', 'preferred_countries' => [], 'position' => 1],
            ['gateway' => 'pawapay', 'preferred_countries' => ['GA'], 'position' => 2],
            ['gateway' => 'maishapay', 'preferred_countries' => ['CG', 'CD'], 'position' => 3],
            ['gateway' => 'digitwace', 'preferred_countries' => ['CM'], 'position' => 4],
        ];

        foreach ($defaults as $row) {
            DB::table('gateway_settings')->insert([
                'gateway' => $row['gateway'],
                'collect_enabled' => true,
                'payout_enabled' => true,
                'preferred_countries' => json_encode($row['preferred_countries']),
                'position' => $row['position'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_settings');
    }
};
