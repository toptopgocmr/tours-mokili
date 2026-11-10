<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Concessionnaires officiels presents au Congo, affiches sous Voiture > Vente.
// Telephones, horaires et photos a completer depuis l'admin (Partenaires).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        $dealers = [
            [
                'name' => 'CFAO Motors Congo - Brazzaville',
                'slug' => 'cfao-motors-congo-brazzaville',
                'city' => 'Brazzaville',
                'address' => null,
                'description' => 'Distributeur exclusif au Congo des marques Toyota, Suzuki et Peugeot (et des pneus Bridgestone). Vehicules neufs, reprise de votre ancien vehicule, financement avec les banques locales, garantie constructeur et services aux flottes d\'entreprise.',
                'services' => ['Toyota', 'Suzuki', 'Peugeot', 'Vehicules neufs', 'Reprise', 'Financement', 'Garantie constructeur', 'Atelier'],
                'is_featured' => true,
            ],
            [
                'name' => 'CFAO Motors Congo - Pointe-Noire',
                'slug' => 'cfao-motors-congo-pointe-noire',
                'city' => 'Pointe-Noire',
                'address' => '13, rue Cote Mateve',
                'description' => 'Agence de Pointe-Noire de CFAO Motors Congo : Toyota, Suzuki et Peugeot neufs, reprise, financement, pieces et atelier agree.',
                'services' => ['Toyota', 'Suzuki', 'Peugeot', 'Vehicules neufs', 'Reprise', 'Financement', 'Atelier'],
                'is_featured' => true,
            ],
            [
                'name' => 'Tractafric Motors Congo',
                'slug' => 'tractafric-motors-congo',
                'city' => 'Brazzaville',
                'address' => null,
                'description' => 'Filiale du groupe Optorg, distributeur au Congo des vehicules Mercedes-Benz (particuliers et industriels), Mitsubishi et des camions Fuso. Vente, pieces d\'origine et service apres-vente.',
                'services' => ['Mercedes-Benz', 'Mitsubishi', 'Fuso', 'Vehicules neufs', 'Utilitaires & camions', 'Pieces d\'origine', 'Atelier'],
                'is_featured' => false,
            ],
        ];

        foreach ($dealers as $d) {
            if (DB::table('businesses')->where('slug', $d['slug'])->exists()) {
                continue;
            }
            DB::table('businesses')->insert([
                'name' => $d['name'],
                'slug' => $d['slug'],
                'category' => 'concessionnaire',
                'city' => $d['city'],
                'address' => $d['address'],
                'country' => 'CG',
                'description' => $d['description'],
                'services' => json_encode($d['services']),
                'is_featured' => $d['is_featured'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('businesses')) {
            DB::table('businesses')->where('category', 'concessionnaire')
                ->whereIn('slug', ['cfao-motors-congo-brazzaville', 'cfao-motors-congo-pointe-noire', 'tractafric-motors-congo'])->delete();
        }
    }
};
