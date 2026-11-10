<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Tourisme > Visite : sites touristiques du Congo (Brazzaville, Pointe-Noire)
// + demandes de visite ("Reserver ma visite", sans engagement).
// Photos : Wikimedia Commons (licences libres). Les sites sans photo
// libre gardent la couverture par defaut : ajoutez les vraies photos
// depuis l'admin (Societes partenaires).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visit_requests')) {
            Schema::create('visit_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $t->string('name');
                $t->string('phone', 40);
                $t->string('email')->nullable();
                $t->date('visit_date');
                $t->unsignedSmallInteger('people')->default(1);
                $t->string('formula', 20)->default('individuel');
                $t->text('message')->nullable();
                $t->string('status', 20)->default('nouvelle');
                $t->timestamps();
                $t->index(['status', 'visit_date']);
            });
        }

        if (! Schema::hasTable('businesses')) {
            return;
        }

        $sites = json_decode(<<<'JSON'
[
 {
  "name": "Galerie Art Brazza",
  "city": "Brazzaville",
  "district": "Poto-Poto / Centre-ville",
  "address": "213, rue de la Musique Tambourinee",
  "tags": [
   "Visite guidee",
   "Art contemporain",
   "Galerie"
  ],
  "desc": "Galerie d'art contemporain au coeur de Poto-Poto. Peintures, photographies d'art, sculptures en bronze, en bois ou en materiaux recycles et mobilier d'art sculpte se partagent un showroom lumineux.\n\nL'atelier d'encadrement sur mesure est sur place, et l'equipe prend le temps de presenter les artistes et l'histoire des oeuvres. Ideal pour une visite culturelle ou l'achat d'une piece unique.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Mayk_Derinck_à_la_Galerie_Art_Brazza.png?width=1280"
  ]
 },
 {
  "name": "Kintele's Garden - Espace barbecue",
  "city": "Brazzaville",
  "district": "Kintele (Pool)",
  "address": "Commune de Kintele, a 15-20 min du centre de Brazzaville",
  "tags": [
   "Loisirs en plein air",
   "Grillades",
   "Famille & groupes"
  ],
  "desc": "Espace de loisirs et restaurant en plein air au bord d'une riviere, dans un cadre tres vert. Specialite de grillades au feu de bois.\n\nL'ambiance est festive et familiale, surtout le dimanche : parfait pour une sortie entre amis, en famille ou en groupe.",
  "imgs": []
 },
 {
  "name": "Miss Loufoulakari",
  "city": "Pointe-Noire",
  "district": "Patra",
  "address": "Patra, en face de l'hopital",
  "tags": [
   "Detente",
   "Nature",
   "Artisanat"
  ],
  "desc": "Un coin de fraicheur et de detente niche dans la verdure, ou la nature rencontre le charme de l'artisanat local. Paillotes, espaces ombrages et decor fait main pour passer une journee au calme.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_03.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_01.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_02.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_07.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_09.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Miss_Loufoulakari_12.jpg?width=1280"
  ]
 },
 {
  "name": "Le Nid de Diazo",
  "city": "Pointe-Noire",
  "district": "Loango",
  "address": "Loango, departement du Kouilou",
  "tags": [
   "Detente",
   "Nature",
   "Week-end"
  ],
  "desc": "Un ecrin de verdure tropicale borde de palmiers a Loango, a quelques kilometres de Pointe-Noire. Une parenthese au calme pour se ressourcer le temps d'une journee ou d'un week-end.",
  "imgs": []
 },
 {
  "name": "La Villegiature de Kintele",
  "city": "Brazzaville",
  "district": "Kintele",
  "address": "Apres l'arret de la station Africa, Kintele",
  "tags": [
   "Detente",
   "Nature",
   "Famille & groupes"
  ],
  "desc": "Un havre de paix aux portes de Brazzaville : espaces verts, calme et air pur pour une pause loin de l'agitation de la ville, en famille ou entre amis.",
  "imgs": []
 },
 {
  "name": "Cite des Grands Lacs",
  "city": "Pointe-Noire",
  "district": "Yanga (Kouilou)",
  "address": "Village Yanga, departement du Kouilou",
  "tags": [
   "Complexe touristique",
   "Lacs",
   "Nature"
  ],
  "desc": "Complexe touristique au coeur du village de Yanga, au milieu des lacs du Kouilou. Baignade, balades au bord de l'eau et detente dans un decor naturel preserve.",
  "imgs": []
 },
 {
  "name": "Basilique Sainte-Anne-du-Congo",
  "city": "Brazzaville",
  "district": "Poto-Poto",
  "address": "Avenue de l'Eglise, Poto-Poto",
  "tags": [
   "Visite guidee",
   "Patrimoine",
   "Monument religieux"
  ],
  "desc": "L'un des monuments les plus celebres de Brazzaville. Construite a partir de 1943 sur les plans de l'architecte Roger Erell, la basilique se reconnait a sa toiture de tuiles vertes et a ses grandes arches en ogive inspirees de l'art local.\n\nUn lieu de culte toujours tres frequente, et une visite incontournable pour l'architecture.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Basilique_Sainte-anne.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Brazzaville_-_Basilique_Sainte-Anne-du-Congo.jpg?width=1280"
  ]
 },
 {
  "name": "Cathedrale du Sacre-Coeur",
  "city": "Brazzaville",
  "district": "Centre-ville",
  "address": "Butte de l'Aiglon, Centre-ville",
  "tags": [
   "Visite guidee",
   "Patrimoine",
   "Monument religieux"
  ],
  "desc": "La plus ancienne cathedrale d'Afrique centrale, consacree en 1894. Elle domine le centre-ville depuis la butte de l'Aiglon et temoigne des debuts de Brazzaville.\n\nLes photos d'epoque ci-dessous montrent l'edifice au debut du XXe siecle.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Cathédrale_de_Brazzaville.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Cathédrale_de_Brazzaville_vue_de_l'abside.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Intérieur_de_la_cathédrale_de_Brazzaville-1907.jpg?width=1280"
  ]
 },
 {
  "name": "Ecole de peinture de Poto-Poto",
  "city": "Brazzaville",
  "district": "Moungali (4e arr.)",
  "address": "Rue Mayama, Moungali",
  "tags": [
   "Visite guidee",
   "Art",
   "Atelier"
  ],
  "desc": "Fondee en 1951, l'Ecole de Poto-Poto est le berceau de la peinture congolaise moderne et a fait connaitre ses artistes dans le monde entier.\n\nOn y visite les ateliers, on y rencontre les peintres au travail et on peut acheter directement leurs toiles.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_03.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_02.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_04.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_05.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_06.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Ecole_de_peinture_de_Poto-Poto_Brazzaville_07.jpg?width=1280"
  ]
 },
 {
  "name": "Institut Francais du Congo (IFC)",
  "city": "Brazzaville",
  "district": "Centre-ville",
  "address": "Rond-point CCF, place de la Republique",
  "tags": [
   "Culture",
   "Spectacles",
   "Expositions"
  ],
  "desc": "Principal lieu culturel du centre-ville : expositions, concerts, theatre, cinema, mediatheque et cafe. Consultez la programmation sur place pour profiter d'un spectacle pendant votre visite.",
  "imgs": []
 },
 {
  "name": "Memorial Pierre Savorgnan de Brazza",
  "city": "Brazzaville",
  "district": "Centre-ville",
  "address": "Centre-ville, au bord du fleuve Congo",
  "tags": [
   "Visite guidee",
   "Musee",
   "Patrimoine"
  ],
  "desc": "Monument de marbre blanc inaugure en 2006, il abrite le mausolee de l'explorateur Pierre Savorgnan de Brazza et un musee retracant la fondation de la ville. Les jardins donnent sur le fleuve Congo.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_03.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_01.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_02.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_06.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_05.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Memorial_pierre_Savorgnan_de_Brazza_08.jpg?width=1280"
  ]
 },
 {
  "name": "Zoolandia Parc",
  "city": "Brazzaville",
  "district": "Centre-ville",
  "address": "Boulevard du Marechal Lyautey",
  "tags": [
   "Loisirs",
   "Famille & groupes",
   "Enfants"
  ],
  "desc": "Parc de loisirs en ville, pense pour les sorties en famille et les groupes scolaires. Renseignez-vous sur les horaires et les activites du jour en reservant votre visite.",
  "imgs": []
 },
 {
  "name": "Ferme Lototi",
  "city": "Brazzaville",
  "district": "Kintele",
  "address": "Derriere le stade de Kintele",
  "tags": [
   "Nature",
   "Agrotourisme",
   "Famille & groupes"
  ],
  "desc": "Ferme aux portes de Brazzaville, pres du stade de Kintele : une sortie au grand air pour petits et grands. Les activites proposees sont precisees a la reservation.",
  "imgs": []
 },
 {
  "name": "Cercle Musee Africain",
  "city": "Pointe-Noire",
  "district": "Marche de la Frontiere",
  "address": "Rond-point E.-P. Lumumba, marche de la Frontiere",
  "tags": [
   "Musee",
   "Culture",
   "Patrimoine"
  ],
  "desc": "Lieu culturel et musee au coeur de Pointe-Noire, pres du marche de la Frontiere, consacre au patrimoine et aux arts africains.",
  "imgs": []
 },
 {
  "name": "Musee national du Congo",
  "city": "Brazzaville",
  "district": "Talangai - M'pila",
  "address": "Talangai, quartier des logements modernes de M'pila",
  "tags": [
   "Visite guidee",
   "Musee",
   "Patrimoine"
  ],
  "desc": "Le musee national, a Talangai, presente le patrimoine historique et culturel du Congo. Photos prises lors de son inauguration.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_03.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_02.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_04.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_06.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_05.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Inauguration_du_musée_national_République_du_Congo_08.jpg?width=1280"
  ]
 },
 {
  "name": "Centre culturel Jean-Baptiste Tati Loutard",
  "city": "Pointe-Noire",
  "district": "M'pita",
  "address": "Rond-point M'pita",
  "tags": [
   "Culture",
   "Spectacles",
   "Expositions"
  ],
  "desc": "Centre culturel qui porte le nom du poete congolais Tati Loutard : spectacles, expositions, ateliers et rencontres autour de la creation locale.",
  "imgs": []
 },
 {
  "name": "Lawanda Tours & Adventure",
  "city": "Pointe-Noire",
  "district": "Centre-ville",
  "address": "Avenue Germain Bicoumat, en face de la BGFI Bank",
  "tags": [
   "Excursions",
   "Visite guidee",
   "Groupes"
  ],
  "desc": "Agence d'excursions et d'aventures basee au centre-ville de Pointe-Noire : sorties guidees pour decouvrir la ville et le Kouilou, en individuel ou en groupe.",
  "imgs": []
 },
 {
  "name": "Boyokani na Loubi",
  "city": "Pointe-Noire",
  "district": "Djeno",
  "address": "Djeno, Pointe-Noire",
  "tags": [
   "Plage",
   "Detente",
   "Famille & groupes"
  ],
  "desc": "Site balneaire a Djeno, au sud de Pointe-Noire : plage, paillotes et restauration pour une journee au bord de l'ocean.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_03.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_06.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_09.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_01.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_02.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Boyokani_na_Loubi_04.jpg?width=1280"
  ]
 },
 {
  "name": "Musee Ma Loango",
  "city": "Pointe-Noire",
  "district": "Diosso (Kouilou)",
  "address": "Diosso, departement du Kouilou",
  "tags": [
   "Visite guidee",
   "Musee",
   "Patrimoine"
  ],
  "desc": "Installe dans l'ancienne residence royale de Diosso, le musee raconte l'histoire du royaume de Loango et de la traite atlantique. A combiner avec les gorges de Diosso toutes proches.",
  "imgs": [
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_12.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_16.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_20.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_25.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_28.jpg?width=1280",
   "https://commons.wikimedia.org/wiki/Special:FilePath/Musée_loango_Diosso_35.jpg?width=1280"
  ]
 }
]
JSON, true);

        foreach ($sites as $s) {
            $slug = Str::slug($s['name']);
            if (DB::table('businesses')->where('slug', $slug)->orWhere(fn ($q) => $q->where('name', $s['name'])->where('category', 'site_touristique'))->exists()) {
                continue;
            }
            DB::table('businesses')->insert([
                'name' => $s['name'],
                'slug' => $slug,
                'category' => 'site_touristique',
                'city' => $s['city'],
                'district' => $s['district'],
                'address' => $s['address'],
                'country' => 'CG',
                'description' => $s['desc'],
                'services' => json_encode($s['tags'], JSON_UNESCAPED_UNICODE),
                'price_range' => 'Gratuit',
                'image' => $s['imgs'][0] ?? null,
                'gallery' => $s['imgs'] ? json_encode(array_slice($s['imgs'], 1), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'is_featured' => ! empty($s['imgs']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_requests');
    }
};
