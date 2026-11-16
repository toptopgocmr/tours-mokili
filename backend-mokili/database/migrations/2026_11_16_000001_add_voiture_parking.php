<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Voiture > Parking : parkings, gardiennage et lavage auto (retires de
 * Voiture > Mecanique).
 *  - le centre de gardiennage Etosha passe dans la categorie "parking" ;
 *  - premiers parkings / lavages connus (aeroports, OpenStreetMap), a
 *    verifier et completer (telephone, photos) depuis /admin/partenaires.
 * Relancer la migration ne cree pas de doublon.
 */
return new class extends Migration
{
    private const PARKINGS = [
        // [nom, ville, quartier, adresse, telephone, description, prestations]
        ["Parking de l'aeroport Maya-Maya", 'Brazzaville', 'Maya-Maya', 'Aeroport international Maya-Maya', null,
            "Parking de l'aeroport international de Brazzaville, pour deposer ou attendre un voyageur et pour les stationnements de courte ou longue duree.",
            ['Parking']],
        ["Parking de l'aeroport Agostinho-Neto", 'Pointe-Noire', null, 'Aeroport international Agostinho-Neto', null,
            "Parking de l'aeroport international de Pointe-Noire, courte et longue duree.",
            ['Parking']],
        ['Lavage auto - Parking OCH', 'Brazzaville', 'OCH', 'Boulevard Marechal Lyautey', null,
            'Station de lavage auto avec parking, pres du boulevard Marechal Lyautey.',
            ['Lavage', 'Parking', 'Nettoyage interieur']],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        DB::table('businesses')->where('slug', 'etosha-centre-service-gardiennage-auto')
            ->update(['category' => 'parking', 'services' => json_encode(['Gardiennage', 'Parking', 'Entretien / revision']), 'updated_at' => now()]);

        foreach (self::PARKINGS as [$name, $city, $district, $address, $phone, $description, $services]) {
            $slug = Str::slug($name);
            if (DB::table('businesses')->where('slug', $slug)->exists()) {
                continue;
            }
            DB::table('businesses')->insert([
                'name' => $name, 'slug' => $slug, 'category' => 'parking', 'city' => $city, 'district' => $district,
                'address' => $address, 'country' => 'CG', 'phone' => $phone, 'description' => $description,
                'services' => json_encode($services, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'is_featured' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }
        DB::table('businesses')->where('slug', 'etosha-centre-service-gardiennage-auto')
            ->update(['category' => 'garage', 'services' => json_encode(['Gardiennage', 'Entretien / revision'])]);
        DB::table('businesses')->whereIn('slug', collect(self::PARKINGS)->map(fn ($p) => Str::slug($p[0])))->delete();
    }
};
