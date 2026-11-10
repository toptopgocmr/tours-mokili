<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Espace partenaires transport : compagnies de bus, train, canots rapides,
 * taxis, compagnies aeriennes, agences de billets, garages.
 *  - users : nom de la societe, activite, date de validation par un admin ;
 *  - travel_offers : proprietaire (partenaire) + promotion (libelle, fin) ;
 *  - vehicles : promotion (garages, location, vente).
 * Colonnes ajoutees seulement si absentes (deploiements interrompus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'partner_activity')) {
                $table->string('partner_activity', 40)->nullable();
            }
            if (! Schema::hasColumn('users', 'partner_verified_at')) {
                $table->timestamp('partner_verified_at')->nullable();
            }
        });
        // Les partenaires deja crees par l'administration sont consideres valides.
        DB::table('users')->where('role', 'partner')->whereNull('partner_verified_at')->update(['partner_verified_at' => now()]);

        Schema::table('travel_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_offers', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->index();
            }
            if (! Schema::hasColumn('travel_offers', 'promo_label')) {
                $table->string('promo_label', 60)->nullable();
            }
            if (! Schema::hasColumn('travel_offers', 'promo_ends_at')) {
                $table->dateTime('promo_ends_at')->nullable();
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'discount_percent')) {
                $table->unsignedTinyInteger('discount_percent')->default(0);
            }
            if (! Schema::hasColumn('vehicles', 'promo_label')) {
                $table->string('promo_label', 60)->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'promo_ends_at')) {
                $table->dateTime('promo_ends_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['users' => ['company_name', 'partner_activity', 'partner_verified_at'], 'travel_offers' => ['owner_id', 'promo_label', 'promo_ends_at'], 'vehicles' => ['discount_percent', 'promo_label', 'promo_ends_at']] as $t => $cols) {
            foreach ($cols as $c) {
                if (Schema::hasColumn($t, $c)) {
                    Schema::table($t, fn (Blueprint $table) => $table->dropColumn($c));
                }
            }
        }
    }
};
