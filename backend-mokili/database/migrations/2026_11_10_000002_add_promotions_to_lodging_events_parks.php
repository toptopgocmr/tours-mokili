<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace partenaires (hotels, appartements, organisateurs de concerts,
 * cinemas, parcs d'attractions) : promotions sur logements, evenements et
 * parcs ; un parc peut appartenir a un partenaire (owner_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['lodging_listings', 'events', 'attraction_parks'] as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                if (! Schema::hasColumn($t, 'discount_percent')) {
                    $table->unsignedTinyInteger('discount_percent')->default(0);
                }
                if (! Schema::hasColumn($t, 'promo_label')) {
                    $table->string('promo_label', 60)->nullable();
                }
                if (! Schema::hasColumn($t, 'promo_ends_at')) {
                    $table->dateTime('promo_ends_at')->nullable();
                }
            });
        }
        if (! Schema::hasColumn('attraction_parks', 'owner_id')) {
            Schema::table('attraction_parks', fn (Blueprint $table) => $table->unsignedBigInteger('owner_id')->nullable()->index());
        }
    }

    public function down(): void
    {
        foreach (['lodging_listings', 'events', 'attraction_parks'] as $t) {
            foreach (['discount_percent', 'promo_label', 'promo_ends_at'] as $c) {
                if (Schema::hasColumn($t, $c)) {
                    Schema::table($t, fn (Blueprint $table) => $table->dropColumn($c));
                }
            }
        }
        if (Schema::hasColumn('attraction_parks', 'owner_id')) {
            Schema::table('attraction_parks', fn (Blueprint $table) => $table->dropColumn('owner_id'));
        }
    }
};
