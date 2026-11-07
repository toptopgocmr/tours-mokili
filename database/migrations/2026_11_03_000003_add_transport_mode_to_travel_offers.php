<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Circuits : moyen de transport (avion, bus, bateau, train, voiture/4x4,
// combine). Les vols restent "avion" ; les anciens circuits sans mode
// passent en "avion" s'ils ont une compagnie, sinon restent vides.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('travel_offers', 'transport_mode')) {
    Schema::table('travel_offers', function (Blueprint $table) {
                $table->string('transport_mode', 20)->nullable()->after('type')->index();
                $table->string('departure_point')->nullable()->after('origin_country');
                $table->string('duration_label')->nullable()->after('return_at');
            });
        }

        DB::table('travel_offers')->where('type', 'vol')->update(['transport_mode' => 'avion']);
        DB::table('travel_offers')->whereNull('transport_mode')->whereNotNull('airline')
            ->where('airline', '!=', '')->update(['transport_mode' => 'avion']);

        // Le train est un transporteur partenaire de la rubrique Voyage.
        if (Schema::hasTable('businesses') && ! DB::table('businesses')->where('name', 'CFCO - Chemin de fer Congo-Ocean')->exists()) {
            DB::table('businesses')->insert([
                'name' => 'CFCO - Chemin de fer Congo-Ocean',
                'slug' => 'cfco-chemin-de-fer-congo-ocean',
                'category' => 'transport_ferroviaire',
                'city' => 'Brazzaville',
                'district' => 'Gare centrale',
                'address' => 'Gare de Brazzaville',
                'country' => 'CG',
                'description' => 'Ligne ferroviaire Brazzaville - Pointe-Noire via Dolisie et Nkayi. Horaires et tarifs a confirmer en gare.',
                'services' => json_encode(['Brazzaville - Pointe-Noire', 'Dolisie', 'Nkayi']),
                'is_featured' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('travel_offers', function (Blueprint $table) {
            $table->dropIndex(['transport_mode']);
            $table->dropColumn(['transport_mode', 'departure_point', 'duration_label']);
        });
    }
};
