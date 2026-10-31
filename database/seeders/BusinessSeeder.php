<?php

namespace Database\Seeders;

use App\Models\Guide\Business;
use Illuminate\Database\Seeder;

/**
 * Carnet d'adresses de depart du GUIDE CONGO.
 *
 * Informations issues de recherches publiques (oct. 2026) : adresses,
 * categories et etoiles sont A VERIFIER avant mise en avant commerciale.
 * Les fiches non confirmees sont creees masquees (is_active = false).
 * Pas de photo par defaut : chaque fiche affiche une illustration de sa
 * categorie tant que l'admin n'a pas ajoute une photo (upload ou URL).
 *
 * Idempotent : une fiche existante (meme nom) n'est jamais ecrasee.
 */
class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::businesses() as $row) {
            if (! Business::where('name', $row['name'])->exists()) {
                Business::create($row + ['city' => 'Brazzaville']);
            }
        }
    }

    public static function businesses(): array
    {
        $hotel = fn (string $name, ?int $stars, string $address, string $district, string $desc, array $extra = []) => array_merge([
            'name' => $name, 'category' => 'hotel', 'stars' => $stars, 'address' => $address, 'district' => $district,
            'description' => $desc, 'services' => ['Chambres', 'Restaurant', 'Wi-Fi'],
        ], $extra);

        return [
            // ---------------- HOTELS ----------------
            $hotel('Grand Lancaster Brazzaville', 5, 'Avenue Auxence Ickonga', 'Centre-ville', 'Hotel haut de gamme du centre-ville de Brazzaville.', ['is_featured' => true]),
            $hotel('Hilton Brazzaville Les Tours Jumelles', 5, 'Les Tours Jumelles de Mpila', 'Mpila', 'Hotel & residences dans les Tours Jumelles, avec vue sur le fleuve Congo.', ['is_featured' => true, 'services' => ['Chambres', 'Residences', 'Restaurant', 'Piscine', 'Salles de conference', 'Wi-Fi']]),
            $hotel("Radisson Blu M'Bamou Palace Hotel", 5, 'Corniche, avenue Amilcar Cabral', 'Centre-ville', 'Hotel 5 etoiles sur la Corniche, face au fleuve Congo.', ['is_featured' => true, 'services' => ['Chambres', 'Restaurant', 'Piscine', 'Salles de conference', 'Wi-Fi']]),
            $hotel('Pefaco Hotel Maya-Maya', 5, 'Boulevard Denis Sassou Nguesso', 'Maya-Maya (aeroport)', "Hotel proche de l'aeroport international Maya-Maya."),
            $hotel('Hotel Ledger Plaza Maya-Maya', 5, 'Brazzaville', 'Maya-Maya', 'Hotel haut de gamme de Brazzaville.'),
            $hotel('Kempinski Hotel Brazzaville', 5, 'Avenue Amilcar Cabral', 'Centre-ville', 'Fiche a confirmer avant publication.', ['is_active' => false]),
            $hotel('Olympic Palace', 4, "Avenue de l'Amitie", 'Centre-ville', "Hotel 4 etoiles sur l'avenue de l'Amitie."),
            $hotel('GHS Hotel', 4, 'Avenue P.E. Lumumba', 'Centre-ville', 'Hotel 4 etoiles avenue Patrice Emery Lumumba.'),
            $hotel("Mikhael's Hotel", 3, 'Coeur de Brazzaville', 'Centre-ville', "Hotel au coeur de Brazzaville, proche de l'aeroport et du centre."),
            $hotel('Hotel Le Paradis', null, "Avenue de l'Amitie", 'Centre-ville', "Hotel de l'avenue de l'Amitie."),
            $hotel('Hotel Africa', null, 'Poto-Poto', 'Poto-Poto', 'Hotel du quartier Poto-Poto.'),

            // ---------------- COMPAGNIES AERIENNES ----------------
            ['name' => 'ECAir (Equatorial Congo Airlines)', 'category' => 'compagnie_aerienne', 'description' => 'Compagnie aerienne nationale congolaise (reprise des activites a confirmer).', 'services' => ['Vols interieurs', 'Vols regionaux']],
            ['name' => 'Brazza Airlines', 'category' => 'compagnie_aerienne', 'description' => 'Compagnie nationale desservant les lignes interieures : Brazzaville, Pointe-Noire, Ollombo, Ouesso, Impfondo.', 'services' => ['Brazzaville', 'Pointe-Noire', 'Ollombo', 'Ouesso', 'Impfondo']],
            ['name' => 'Canadian Airways Congo', 'category' => 'compagnie_aerienne', 'description' => 'Liaisons regulieres interieures, notamment Brazzaville - Pointe-Noire.', 'services' => ['Brazzaville - Pointe-Noire']],
            ['name' => 'Afrijet Business Service', 'category' => 'compagnie_aerienne', 'description' => 'Liaisons regionales dans le Golfe de Guinee et vers les pays voisins.', 'services' => ['Vols regionaux']],
            ['name' => 'Air France', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => 'Liaisons internationales vers Paris.', 'services' => ['Paris']],
            ['name' => 'Ethiopian Airlines', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => "Liaisons internationales via Addis-Abeba (Afrique, Europe, Asie, Ameriques).", 'services' => ['Addis-Abeba', 'Correspondances internationales']],
            ['name' => 'RwandAir', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => 'Liaisons via Kigali.', 'services' => ['Kigali']],
            ['name' => 'Royal Air Maroc', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => 'Liaisons via Casablanca vers l\'Europe et l\'Amerique.', 'services' => ['Casablanca']],
            ['name' => 'ASKY Airlines', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => "Reseau regional ouest et centre africain via Lome.", 'services' => ['Lome', 'Afrique de l\'Ouest']],
            ['name' => 'TAAG Angola Airlines', 'category' => 'compagnie_aerienne', 'district' => 'Aeroport Maya-Maya', 'description' => 'Liaisons via Luanda.', 'services' => ['Luanda']],

            // ---------------- TRANSPORT ROUTIER ----------------
            ['name' => 'Ocean du Nord', 'category' => 'transport_routier', 'description' => 'Leader du transport routier interurbain : departs quotidiens sur les grands axes (Brazzaville, Pointe-Noire, Ouesso, Oyo). Cartes de fidelite ONC.', 'services' => ['Brazzaville - Pointe-Noire', 'Ouesso', 'Oyo', 'Carte ONC'], 'is_featured' => true],
            ['name' => 'Nzoko Transport', 'category' => 'transport_routier', 'description' => 'Bus modernes avec Wi-Fi et confort, trajets reguliers Brazzaville - Pointe-Noire, Dolisie, Ouesso, avec billetterie en ligne.', 'services' => ['Brazzaville - Pointe-Noire', 'Dolisie', 'Ouesso', 'Wi-Fi a bord', 'Billetterie en ligne'], 'is_featured' => true],

            // ---------------- TAXIS & VTC ----------------
            ['name' => 'Gozem', 'category' => 'taxi_vtc', 'description' => "Application de reference pour commander un taxi a Brazzaville (standard, climatise ou a l'heure), paiement en especes ou Mobile Money (Airtel / MTN). Lancee a Brazzaville en novembre 2025.", 'services' => ['Taxi', 'Taxi climatise', "Course a l'heure", 'Mobile Money'], 'website' => 'https://gozem.co', 'is_featured' => true],
            ['name' => 'Drivigo', 'category' => 'taxi_vtc', 'description' => "VTC premium et chauffeurs prives (SUV et vans), ideal pour les transferts aeroport Maya-Maya et les longues distances.", 'services' => ['Transfert aeroport', 'Chauffeur prive', 'SUV / vans'], 'website' => 'https://www.drivigo.fr'],
            ['name' => 'Express Drive', 'category' => 'taxi_vtc', 'description' => 'Agence locale de VTC pour les deplacements professionnels et prives en ville.', 'services' => ['VTC', 'Deplacements professionnels']],

            // ---------------- CANOTS RAPIDES / FLUVIAL ----------------
            ['name' => 'Beach de Brazzaville - liaison Kinshasa', 'category' => 'canot_rapide', 'district' => 'Le Beach', 'address' => 'Port fluvial du Beach', 'description' => 'Traversee du fleuve Congo entre Brazzaville et Kinshasa en canot rapide ou bateau. Passeport / laissez-passer obligatoire.', 'services' => ['Canots rapides', 'Bateaux', 'Brazzaville - Kinshasa']],

            // ---------------- SUPERMARCHES ----------------
            ['name' => "Park'n Shop", 'category' => 'supermarche', 'district' => 'Centre-ville / Mazala', 'description' => "L'un des supermarches les plus complets : produits importes, frais et epicerie fine. Presents en centre-ville et a Mazala.", 'services' => ['Produits frais', 'Produits importes', 'Epicerie fine']],
            ['name' => 'Cosmos Supermarche', 'category' => 'supermarche', 'district' => 'Plateau', 'description' => 'Grande surface offrant une large selection de produits du quotidien a prix abordables.', 'services' => ['Produits du quotidien']],

            // ---------------- RESTAURANTS ----------------
            ['name' => 'Esengo Restaurant and Lounge Bar', 'category' => 'restaurant', 'address' => 'Avenue de la Corniche, 117 rue Mbamba', 'district' => 'Corniche', 'description' => 'Cuisine de qualite et cocktails avec vue panoramique sur le fleuve.', 'services' => ['Restaurant', 'Cocktails', 'Vue sur le fleuve']],
            ['name' => 'Apero', 'category' => 'restaurant', 'description' => "L'une des meilleures tables gastronomiques de la capitale, tres haut de gamme.", 'services' => ['Cuisine gastronomique'], 'is_featured' => true],
            ['name' => "L'Assiette", 'category' => 'restaurant', 'address' => "Avenue de l'Amitie", 'description' => "Restaurant au rez-de-chaussee et bar lounge a l'etage.", 'services' => ['Plats familiaux', 'Bar lounge']],

            // ---------------- BARS & LOUNGES ----------------
            ['name' => 'Mayson Lounge', 'category' => 'bar_lounge', 'address' => '5 rue Louis, avenue Amilcar Cabral', 'district' => 'Centre-ville', 'description' => 'Adresse tendance du centre-ville, reputee pour sa cuisine, son ambiance soignee et ses espaces lounge.', 'services' => ['Bar', 'Cuisine', 'Lounge']],
            ['name' => 'Pichichi Lounge Bar', 'category' => 'bar_lounge', 'description' => 'Ambiance festive pour boire un verre et ecouter de la musique entre amis.', 'services' => ['Bar', 'Musique']],
            ['name' => 'Le Brazzil', 'category' => 'bar_lounge', 'description' => 'Ambiance festive pour boire un verre et ecouter de la musique entre amis.', 'services' => ['Bar', 'Musique']],

            // ---------------- BOUTIQUES EN LIGNE ----------------
            ['name' => 'MoKoCG', 'category' => 'ecommerce', 'city' => 'Brazzaville', 'district' => 'Brazzaville & Pointe-Noire', 'description' => 'Plateforme de e-commerce a Brazzaville et Pointe-Noire : vente au detail et en gros avec livraison a domicile.', 'services' => ['Detail', 'Gros', 'Livraison a domicile']],
            ['name' => 'Kotaso Marketplace', 'category' => 'ecommerce', 'description' => 'Marche en ligne (mode, tech, electromenager) avec paiement MTN MoMo et Airtel Money.', 'services' => ['Mode', 'Tech', 'Electromenager', 'Mobile Money']],
            ['name' => 'MboteShop', 'category' => 'ecommerce', 'description' => 'Site local de vente en ligne, petites annonces et location.', 'services' => ['Vente en ligne', 'Petites annonces', 'Location']],
        ];
    }
}
