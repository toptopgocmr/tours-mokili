<?php

namespace Database\Seeders;

use App\Models\Loisirs\AttractionPark;
use Illuminate\Database\Seeder;

/**
 * Catalogue de depart des parcs et sites de loisirs du Congo-Brazzaville.
 *
 * Les informations (adresses, telephones, horaires, tarifs) proviennent de
 * sources publiques (Petit Fute, presse congolaise, pages officielles) et
 * doivent etre VERIFIEES aupres de chaque site avant la mise en avant
 * commerciale. Quand un tarif n'est pas connu, price_adult reste null :
 * la fiche s'affiche avec "Tarif communique sur place" et n'est pas
 * reservable en ligne tant que l'admin n'a pas saisi de prix.
 *
 * Idempotent : updateOrCreate sur le nom, on peut le relancer sans doublons.
 */
class AttractionParkSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::parks() as $park) {
            $existing = AttractionPark::where('name', $park['name'])->first();

            if ($existing) {
                continue; // ne jamais ecraser ce que l'admin a corrige
            }

            AttractionPark::create($park);
        }
    }

    public static function parks(): array
    {
        return [
            // ---------------- BRAZZAVILLE ----------------
            [
                'name' => 'Zoolandia - Parc Zoologique et Botanique',
                'category' => 'zoo',
                'city' => 'Brazzaville',
                'district' => 'Patte d\'Oie',
                'address' => 'Boulevard du Marechal Lyautey, reserve forestiere de la Patte d\'Oie',
                'description' => "Vitrine de la faune et de la flore congolaises en plein coeur de Brazzaville : primates (mandrills, cercopitheques, mangabeys), autruches, jardin botanique, et des attractions pour toute la famille. Restauration sur place (Food Corner).",
                'activities' => ['Visite du zoo', 'Jardin botanique', 'Tyrolienne', 'Toboggan', 'Paintball', 'Velo', 'Restaurant'],
                'opening_hours' => 'Tous les jours (horaires a confirmer)',
                'price_adult' => null,
                'price_child' => null,
                'website' => 'https://zoolandia.cg',
                'is_featured' => true,
            ],
            [
                'name' => 'Parc aquatique de Brazzaville (Aquareve)',
                'category' => 'parc_aquatique',
                'city' => 'Brazzaville',
                'district' => 'Maya-Maya (pres de l\'aeroport)',
                'address' => 'Boulevard Denis Sassou Nguesso, face a la tour de controle ASECNA',
                'description' => "Parc aquatique familial pres de l'aeroport Maya-Maya : grande piscine, toboggans, parcours aquatique et aire de jeux pour enfants, avec un surveillant par jeu. Promotions speciales le mardi.",
                'activities' => ['Grande piscine', 'Toboggans', 'Parcours aquatique', 'Aire de jeux enfants', 'Parking'],
                'opening_hours' => 'Tous les jours (horaires a confirmer)',
                'price_adult' => 5000,
                'price_child' => 5000,
                'phone' => '+242 06 768 22 22',
                'is_featured' => true,
            ],
            [
                'name' => 'IMALO - Parc a theme pour enfants',
                'category' => 'parc_attractions',
                'city' => 'Brazzaville',
                'district' => 'Poto-Poto / Ouenze',
                'address' => '131, rue Mbochis (croisement Madoukou), Ouenze',
                'description' => "Parc a theme couvert pour les enfants de 0 a 14 ans : une ville miniature ou les enfants jouent aux metiers - mini-pompiers (Imalo Tiya), espace police, ecole, infirmerie (Imalo Hospital), marche des petits curieux (Imalo Zando) et restaurant (Imalo Resto) - plus une Game Zone.",
                'activities' => ['Ville miniature', 'Game Zone', 'Mini-pompiers (Imalo Tiya)', 'Espace police', 'Ecole', 'Imalo Hospital', 'Marche Imalo Zando', 'Imalo Resto'],
                'opening_hours' => 'Mar-jeu 10h-18h, ven-sam 10h-19h, dim 12h-19h (ferme le lundi)',
                'min_age' => '0 a 14 ans',
                'price_adult' => null,
                'image' => '/images/loisirs/imalo-facade.webp',
                'gallery' => [
                    '/images/loisirs/imalo-facade.webp',
                    '/images/loisirs/imalo-resto.webp',
                    '/images/loisirs/imalo-ecole.webp',
                    '/images/loisirs/imalo-zando.webp',
                    '/images/loisirs/imalo-resto-salle.webp',
                    '/images/loisirs/imalo-hospital.jpg',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Play Town (Brazza Mall)',
                'category' => 'aire_de_jeux',
                'city' => 'Brazzaville',
                'district' => 'Brazza Mall',
                'address' => 'Enceinte du Brazza Mall',
                'description' => "Espace de jeux interieur de 600 m2, securise et climatise, avec un espace jeux d'eau lumineux. Pratique pour occuper les enfants pendant le shopping.",
                'activities' => ['Jeux interieurs', "Espace jeux d'eau lumineux", 'Climatise'],
                'opening_hours' => 'Horaires du Brazza Mall (a confirmer)',
                'price_adult' => null,
            ],
            [
                'name' => 'Culture Parc Brazzaville',
                'category' => 'parc_attractions',
                'city' => 'Brazzaville',
                'district' => 'Poto-Poto',
                'address' => 'Rond-point Poto-Poto',
                'description' => 'Espace de loisirs et de culture en plein air : cinema, animations et detente en famille.',
                'activities' => ['Cinema', 'Animations', 'Detente en famille'],
                'opening_hours' => 'Horaires a confirmer',
                'price_adult' => null,
            ],
            [
                'name' => 'Parc Marien Ngouabi',
                'category' => 'parc_naturel',
                'city' => 'Brazzaville',
                'district' => 'Poto-Poto',
                'address' => 'Parc Marien Ngouabi, Poto-Poto',
                'description' => 'Parc urbain arbore en plein centre de Brazzaville, pour une promenade ou un pique-nique au calme.',
                'activities' => ['Promenade', 'Pique-nique', 'Espaces verts'],
                'opening_hours' => 'Acces libre en journee',
                'price_adult' => null,
            ],
            [
                'name' => 'Parc Aventures Brazzaville',
                'category' => 'aire_de_jeux',
                'city' => 'Brazzaville',
                'district' => 'Mpila',
                'address' => 'Quartier Mpila',
                'description' => "Parc de jeux et d'animations pour enfants et jeunes : structures de jeux, animations et anniversaires.",
                'activities' => ['Jeux pour enfants', 'Animations', 'Anniversaires'],
                'opening_hours' => 'Horaires a confirmer',
                'price_adult' => null,
                'phone' => '+242 06 413 38 22',
            ],
            [
                'name' => 'Fantasya - Espace enfants',
                'category' => 'aire_de_jeux',
                'city' => 'Brazzaville',
                'district' => 'Centre-ville',
                'address' => '98, avenue Charles de Foucauld',
                'description' => "Centre de loisirs pour enfants au centre-ville : jeux, activites encadrees et gouters d'anniversaire.",
                'activities' => ['Aire de jeux', 'Anniversaires', 'Activites encadrees'],
                'opening_hours' => 'Horaires a confirmer',
                'price_adult' => null,
                'phone' => '+242 06 674 85 85',
            ],
            [
                'name' => 'Nash&Co Fun\'Park',
                'category' => 'aire_de_jeux',
                'city' => 'Brazzaville',
                'district' => 'OCH',
                'address' => 'Quartier OCH',
                'description' => 'Aire de loisirs pour enfants : jeux gonflables, trampolines et espace detente pour les parents.',
                'activities' => ['Jeux gonflables', 'Trampolines', 'Espace parents'],
                'opening_hours' => 'Horaires a confirmer',
                'price_adult' => null,
            ],
            [
                'name' => 'Ile Sakana',
                'category' => 'ile_loisirs',
                'city' => 'Brazzaville',
                'district' => 'Fleuve Congo',
                'address' => 'Navette bateau depuis le centre-ville (embarcadere Mami Wata)',
                'description' => "Ile de loisirs sur le fleuve Congo, accessible en bateau rapide depuis le centre-ville : plage, piscine, salle de sport, restauration et espace enfants (manege, trampoline, structures gonflables).",
                'activities' => ['Plage', 'Piscine', 'Salle de sport', 'Espace enfants', 'Restauration', 'Navette bateau'],
                'opening_hours' => 'Horaires a confirmer (navettes regulieres)',
                'price_adult' => null,
                'is_featured' => true,
            ],
            [
                'name' => 'Les Rapides du Djoue',
                'category' => 'parc_naturel',
                'city' => 'Brazzaville',
                'district' => 'Djoue / Makelekele',
                'address' => 'Peripherie sud-ouest de Brazzaville, au bord du fleuve Congo',
                'description' => "Site naturel spectaculaire ou le fleuve Congo forme de puissants rapides. Ideal pour une sortie detente au bord de l'eau avec bars et restaurants a proximite.",
                'activities' => ['Panorama', 'Balade', 'Bars & restaurants'],
                'opening_hours' => 'Acces libre en journee',
                'price_adult' => null,
            ],

            // ---------------- POINTE-NOIRE ----------------
            [
                'name' => 'Ile O Splash',
                'category' => 'parc_aquatique',
                'city' => 'Pointe-Noire',
                'address' => '77, avenue Ma Loango Moe Poaty',
                'description' => "Parc aquatique bien entretenu, l'un des rares du Congo : piscines et toboggans pour les enfants comme pour les grands.",
                'activities' => ['Piscines', 'Toboggans aquatiques', 'Restauration'],
                'opening_hours' => 'Horaires a confirmer',
                'price_adult' => null,
                'is_featured' => true,
            ],
            [
                'name' => 'Yamakasiparc',
                'category' => 'parc_attractions',
                'city' => 'Pointe-Noire',
                'district' => 'Centre-ville',
                'address' => 'Entree rue Elais (3e rue au stop a droite), face au consulat de la RDC',
                'description' => "Parc de jeux et de loisirs oriente aventure pour les enfants : trampolines, toboggans, petites voitures et parcours d'obstacles. Ideal pour les 7-12 ans, avec des activites pour les plus petits. Bouteille d'eau offerte a chaque enfant.",
                'activities' => ['Trampolines', 'Toboggans', 'Voitures enfants', "Parcours d'obstacles"],
                'opening_hours' => 'Horaires a confirmer',
                'min_age' => 'Enfants 4-11 ans',
                'price_adult' => 7000,
                'price_child' => 5000,
                'phone' => '+242 06 834 94 74',
                'is_featured' => true,
            ],
            [
                'name' => 'Fun Air PN',
                'category' => 'aire_de_jeux',
                'city' => 'Pointe-Noire',
                'district' => 'Wharf',
                'address' => 'Avenue Ngeli Ngeli, quartier Wharf',
                'description' => 'Parc de jeux gonflables pour enfants de 1 a 12 ans, avec snack (pop-corn...). Centre de loisirs pendant les vacances scolaires.',
                'activities' => ['Jeux gonflables', 'Snack', 'Centre de vacances'],
                'opening_hours' => 'Horaires a confirmer',
                'min_age' => '1 a 12 ans',
                'price_adult' => null,
                'phone' => '+242 06 584 60 04',
            ],
            [
                'name' => "Parc d'attractions de la mairie de Tie-Tie (AJMC)",
                'category' => 'aire_de_jeux',
                'city' => 'Pointe-Noire',
                'district' => 'Tie-Tie (3e arrondissement)',
                'address' => 'Enceinte de la mairie du 3e arrondissement Tie-Tie',
                'description' => "Parc installe par l'Association des jeunes meres du Congo (AJMC) : structures gonflables, trampolines et mini-labyrinthes ludiques, surtout pendant les vacances scolaires.",
                'activities' => ['Structures gonflables', 'Trampolines', 'Mini-labyrinthes'],
                'opening_hours' => 'Surtout pendant les vacances scolaires (a confirmer)',
                'price_adult' => null,
            ],
            [
                'name' => 'Gorges de Diosso',
                'category' => 'parc_naturel',
                'city' => 'Pointe-Noire',
                'district' => 'Diosso - Loango',
                'address' => 'Diosso, a environ 20 km de Pointe-Noire',
                'description' => "L'un des plus beaux sites du Congo : un cirque de falaises rouges plongeant dans la foret tropicale, a quelques kilometres de l'ocean. Randonnees et points de vue.",
                'activities' => ['Randonnee', 'Points de vue', 'Photographie'],
                'opening_hours' => 'En journee',
                'price_adult' => null,
            ],

            // ---------------- PARCS NATURELS ----------------
            [
                'name' => 'Reserve de Lesio-Louna (gorilles)',
                'category' => 'parc_naturel',
                'city' => 'Brazzaville',
                'district' => 'Plateaux - a 140 km au nord de Brazzaville',
                'address' => 'Reserve Lefini / Lesio-Louna, route du Nord',
                'description' => "Mosaique de forets et de savanes ou des gorilles des plaines orphelins sont rehabilites. Excursion d'une journee ou d'un week-end depuis Brazzaville, par route goudronnee.",
                'activities' => ['Observation des gorilles', 'Balade en pirogue', 'Savane', 'Excursion guidee'],
                'opening_hours' => 'Sur reservation',
                'price_adult' => null,
                'is_featured' => true,
            ],
            [
                'name' => 'Parc national d\'Odzala-Kokoua',
                'category' => 'parc_naturel',
                'city' => 'Ouesso',
                'district' => 'Cuvette-Ouest / Sangha',
                'description' => "Parc inscrit au patrimoine mondial de l'UNESCO, au coeur du bassin du Congo : gorilles des plaines de l'Ouest, elephants de foret et plus de 400 especes d'oiseaux. Acces par vol depuis Brazzaville.",
                'activities' => ['Pistage des gorilles', 'Safari en foret', 'Kayak', 'Lodges'],
                'opening_hours' => 'Sur reservation (sejours organises)',
                'price_adult' => null,
            ],
            [
                'name' => 'Parc national de Conkouati-Douli',
                'category' => 'parc_naturel',
                'city' => 'Pointe-Noire',
                'district' => 'Kouilou',
                'description' => "Le parc national le plus riche en biodiversite du Congo, entre ocean, lagune et foret : elephants, chimpanzes, tortues marines. Balades en bateau sur la lagune de Conkouati.",
                'activities' => ['Lagune en bateau', 'Randonnee', 'Observation de la faune', 'Plages'],
                'opening_hours' => 'Sur reservation',
                'price_adult' => null,
            ],
        ];
    }
}
