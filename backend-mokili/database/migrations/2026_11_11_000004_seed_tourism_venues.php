<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Tourisme : lieux reels pour les onglets vides ou peu remplis.
 *  - Cinema : salles CanalOlympia (Brazzaville, Pointe-Noire) ;
 *  - Concerts & spectacles : salles et scenes (affichees tant qu'aucun
 *    evenement date n'est publie) ;
 *  - Night club, Bar lounge, Restaurant : adresses citees par la presse et
 *    les guides de voyage.
 * Sources : presse congolaise (Les Depeches de Brazzaville / AllAfrica),
 * Petit Fute, Wikivoyage, sites des hotels (2024-2026). Horaires, telephones
 * et photos officielles a completer dans /admin/partenaires.
 */
return new class extends Migration
{
    /** categorie, nom, ville, quartier, adresse, telephone, horaires, prix, description, services */
    private const VENUES = [
        ['cinema', 'CanalOlympia Poto-Poto', 'Brazzaville', 'Poto-Poto', null, null, null, null,
            "Salle de cinema et de spectacles du reseau CanalOlympia : films a l'affiche, avant-premieres de films congolais et concerts d'artistes locaux.",
            ['Films a l\'affiche', 'Avant-premieres', 'Concerts', 'Climatisation']],
        ['cinema', "CanalOlympia M'Pita", 'Pointe-Noire', "M'Pita", "Croisement de l'avenue Tchicaya U'tamsi et de l'avenue Stanislas Batchi", null, null, null,
            "Salle de cinema CanalOlympia de Pointe-Noire : programmation hebdomadaire, avant-premieres et grands concerts (Fete de la musique, festivals).",
            ['Films a l\'affiche', 'Avant-premieres', 'Concerts', 'Climatisation']],

        ['salle_spectacle', 'Esplanade du Palais des Congres', 'Brazzaville', 'Centre-ville', 'Palais des Congres', null, null, null,
            "Grande scene en plein air de Brazzaville : elle a accueilli des centaines de concerts et revele de nombreux musiciens congolais.",
            ['Concerts', 'Plein air', 'Grands evenements']],
        ['salle_spectacle', 'Institut francais du Congo - Pointe-Noire', 'Pointe-Noire', null, '11, avenue Kaat-Matou', null, null, 'Entree a petit prix',
            "Lieu culturel de reference de Pointe-Noire : concerts, theatre, cinema, expositions et conferences, a petit prix.",
            ['Concerts', 'Theatre', 'Cinema', 'Expositions', 'Mediatheque']],
        ['salle_spectacle', 'Espace culturel Yaro', 'Pointe-Noire', null, null, null, null, null,
            "Espace culturel dedie au theatre et aux arts de la scene ; il accueille notamment le Festival international de theatre et autres arts de la scene (Fitaas).",
            ['Theatre', 'Danse', 'Festivals']],

        ['night_club', 'Buddha Bar', 'Pointe-Noire', null, null, null, null, null,
            "Adresse incontournable des nuits de Pointe-Noire pour danser jusqu'au bout de la nuit.",
            ['Discotheque', 'DJ', 'Cocktails']],
        ['night_club', 'Le Bataclan Bar', 'Brazzaville', 'Moungali', 'Terrasse, a la lisiere de Poto-Poto', null, '7 jours sur 7', null,
            "Bar dancing populaire perche en terrasse, a Moungali : musique a fond, piste de danse et ambiance 100 % congolaise.",
            ['Bar dancing', 'Rumba', 'Terrasse']],
        ['night_club', 'La Maison Blanche', 'Brazzaville', 'Plateau', null, null, null, null,
            "Club sur trois niveaux : musique congolaise, ambiance festive et grillades de rue.",
            ['Club', 'Musique locale', 'Grillades']],

        ['bar_lounge', "M'Bamou Lounge", 'Brazzaville', 'Centre-ville, Corniche', 'Hotel Radisson Blu, avenue Amilcar Cabral', '+242 05 050 6060', 'Tous les jours, 8 h - 23 h', null,
            "Lounge de l'hotel Radisson Blu au bord du fleuve Congo : terrasse exterieure, fauteuils et vue sur le fleuve. Tenue correcte.",
            ['Terrasse', 'Vue sur le fleuve', 'Cocktails', 'After work']],
        ['bar_lounge', 'Mazra Club', 'Pointe-Noire', 'Songolo', 'En bord de mer, pres de la mangrove', null, null, null,
            "Beach club en bord de mer dans le quartier de Songolo, pres d'une mangrove.",
            ['Beach club', 'Bord de mer', 'Cocktails']],

        ['restaurant', 'Cercle Naval (Club Nautique)', 'Pointe-Noire', null, "Pres du marche artisanal, au Yacht Club", '+242 05 557 9998', '12 h - 15 h et 18 h - 22 h 30', null,
            "Restaurant du Yacht Club de Pointe-Noire : terrasse couverte au-dessus de l'eau, grand choix de pizzas, viandes et poissons.",
            ['Terrasse sur l\'eau', 'Pizzas', 'Poissons', 'Viandes']],
    ];

    public function up(): void
    {
        if (app()->environment('testing') || ! Schema::hasTable('businesses')) {
            return;
        }
        foreach (self::VENUES as [$category, $name, $city, $district, $address, $phone, $hours, $price, $description, $services]) {
            $slug = Str::slug($name);
            if (DB::table('businesses')->where('slug', $slug)->exists()) {
                continue;
            }
            DB::table('businesses')->insert([
                'name' => $name, 'slug' => $slug, 'category' => $category, 'city' => $city,
                'district' => $district, 'address' => $address, 'country' => 'CG', 'phone' => $phone,
                'opening_hours' => $hours, 'price_range' => $price, 'description' => $description,
                'services' => json_encode($services, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'is_featured' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('businesses')) {
            DB::table('businesses')->whereIn('slug', array_map(fn ($v) => Str::slug($v[1]), self::VENUES))->delete();
        }
    }
};
