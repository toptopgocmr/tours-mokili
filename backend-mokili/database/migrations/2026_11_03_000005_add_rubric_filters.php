<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filtres par rubrique (voir config/catalogue.php) :
 *  - Voyage / circuits : portee (ville, inter-departements, international) et departements
 *  - Logement : villa, hotel, appartement
 *  - Voiture : location, vente, mecanique
 *  - Marketplace : petites annonces (je vends / je recherche, contact vendeur, photos)
 *  - Fret : types de colis acceptes / type de colis de l'envoi
 *  - Divertissement devient "Tourisme"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_offers', function (Blueprint $table) {
            $table->string('trip_scope', 20)->nullable()->after('transport_mode')->index();
            $table->string('origin_department')->nullable()->after('departure_point');
            $table->string('destination_department')->nullable()->after('origin_department');
        });

        Schema::table('lodging_listings', function (Blueprint $table) {
            $table->string('property_type', 20)->nullable()->after('title')->index();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('offer_type', 20)->default('location')->after('title')->index();
            $table->text('description')->nullable()->after('model');
            $table->decimal('sale_price', 14, 2)->nullable()->after('price_per_day');
            $table->unsignedInteger('mileage_km')->nullable()->after('seats');
            $table->string('fuel', 20)->nullable()->after('mileage_km');
            $table->json('services')->nullable()->after('fuel');
            $table->string('phone')->nullable()->after('country');
        });
        // Vente / mecanique : pas de prix a la journee.
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price_per_day', 12, 2)->nullable()->change();
        });

        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->string('listing_type', 20)->default('vente')->after('title')->index();
            $table->boolean('negotiable')->default(false)->after('price');
            $table->string('district')->nullable()->after('city');
            $table->string('contact_name')->nullable()->after('country');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('contact_whatsapp')->nullable()->after('contact_phone');
            $table->json('gallery')->nullable()->after('image');
            $table->unsignedInteger('views')->default(0)->after('gallery');
            $table->timestamp('sold_at')->nullable()->after('views');
            $table->index(['category', 'city']);
        });

        Schema::table('freight_offers', function (Blueprint $table) {
            $table->json('package_types')->nullable()->after('mode');
            $table->string('transit_time')->nullable()->after('capacity_kg');
        });

        Schema::table('freight_shipments', function (Blueprint $table) {
            $table->string('package_type', 20)->nullable()->after('mode');
            $table->string('package_description')->nullable()->after('package_type');
        });

        // Anciennes categories libres des produits -> categories des annonces.
        $map = [
            'electromenager' => 'maison', 'maison' => 'maison', 'electronique' => 'electronique',
            'informatique' => 'electronique', 'telephone' => 'telephones', 'telephones' => 'telephones',
            'mode' => 'mode', 'accessoires' => 'mode', 'vetements' => 'mode', 'sport' => 'loisirs',
            'vehicule' => 'vehicules', 'vehicules' => 'vehicules', 'immobilier' => 'immobilier',
        ];
        $keys = array_keys(config('catalogue.marketplace.categories'));
        DB::table('marketplace_products')->select('id', 'category')->orderBy('id')->each(function ($p) use ($map, $keys) {
            $current = mb_strtolower(trim((string) $p->category));
            if (! in_array($current, $keys, true)) {
                DB::table('marketplace_products')->where('id', $p->id)->update(['category' => $map[$current] ?? 'autres']);
            }
        });

        DB::table('service_categories')->where('slug', 'divertissement')->update([
            'name' => 'Tourisme',
            'description' => 'Concerts, spectacles, cinema, parcs, night clubs, restaurants, bars lounge et visites.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('service_categories')->where('slug', 'divertissement')->update(['name' => 'Divertissement']);

        Schema::table('freight_shipments', fn (Blueprint $t) => $t->dropColumn(['package_type', 'package_description']));
        Schema::table('freight_offers', fn (Blueprint $t) => $t->dropColumn(['package_types', 'transit_time']));
        Schema::table('marketplace_products', function (Blueprint $t) {
            $t->dropIndex(['category', 'city']);
            $t->dropIndex(['listing_type']);
            $t->dropColumn(['listing_type', 'negotiable', 'district', 'contact_name', 'contact_phone', 'contact_whatsapp', 'gallery', 'views', 'sold_at']);
        });
        Schema::table('vehicles', function (Blueprint $t) {
            $t->dropIndex(['offer_type']);
            $t->dropColumn(['offer_type', 'description', 'sale_price', 'mileage_km', 'fuel', 'services', 'phone']);
        });
        Schema::table('lodging_listings', function (Blueprint $t) {
            $t->dropIndex(['property_type']);
            $t->dropColumn('property_type');
        });
        Schema::table('travel_offers', function (Blueprint $t) {
            $t->dropIndex(['trip_scope']);
            $t->dropColumn(['trip_scope', 'origin_department', 'destination_department']);
        });
    }
};
