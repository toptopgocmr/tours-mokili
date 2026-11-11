<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Voiture :
 *  - vendeurs / concessionnaires supplementaires (Voiture > Vente) ;
 *  - garages, mecanique et depannage a Brazzaville, Pointe-Noire et Ouesso
 *    (Voiture > Mecanique), avec leurs prestations pour la recherche ;
 *  - 22 annonces d'exemple supplementaires (marques chinoises, pick-up,
 *    4x4...) avec photos Wikimedia Commons (licences libres).
 *
 * Coordonnees des societes : annuaire public GoAfrica Online (novembre 2026)
 * et presse locale, a verifier / completer depuis /admin/partenaires.
 * Prestations : seulement celles annoncees par la societe ; sinon
 * "Mecanique generale". Relancer la migration ne cree pas de doublon.
 */
return new class extends Migration
{
    private const DEALERS = [
        ['Prestige Cars', 'Brazzaville', 'Poto-Poto', null, null,
            "Vente de vehicules d'occasion de toutes marques, importes d'Europe et des Etats-Unis, au coeur de Poto-Poto (3e arrondissement).",
            ['Multimarque', "Vehicules d'occasion", 'Import Europe / Etats-Unis', 'Lavage']],
        ['Emmanu Services', 'Brazzaville', 'Talangai', '53, rue Bouanga', '+242 065 02 91 92',
            "Vente et location de vehicules, assistance automobile et routiere 7 jours sur 7, 24 h sur 24.",
            ['Multimarque', 'Vente de vehicules', 'Location', 'Depannage / remorquage', 'Assistance 24h/24']],
        ['SMT Congo - Brazzaville', 'Brazzaville', null, "Avenue Bayardelle, impasse entre l'ambassade du Cameroun et la direction technique d'Airtel", '+242 065 08 27 13',
            "Concessionnaire agree Volvo Trucks : camions neufs, pieces d'origine et service apres-vente pour le transport et les equipements.",
            ['Volvo', 'Volvo Trucks', 'Camions', "Pieces d'origine", 'Atelier', 'Poids lourds']],
        ['SMT Congo - Pointe-Noire', 'Pointe-Noire', 'Centre-ville', "1055, avenue de l'Aeroport", '+242 065 08 27 13',
            "Concessionnaire agree Volvo Trucks a Pointe-Noire : vente de camions, pieces d'origine, atelier poids lourds.",
            ['Volvo', 'Volvo Trucks', 'Camions', "Pieces d'origine", 'Atelier', 'Poids lourds']],
    ];

    /** nom, ville, quartier, adresse, telephone, description, prestations */
    private const GARAGES = [
        // Brazzaville
        ['ESS Business International', 'Brazzaville', 'Ouenze', '42, avenue Jacques Opangault', '+242 068 88 87 87',
            'Restauration automobile, mecanique et carrosserie.', ['Mecanique generale', 'Carrosserie', 'Peinture']],
        ['Trocadero International', 'Brazzaville', 'Mpissa', '124, rue Archambault', '+242 044 84 27 88',
            'Vente et reparation de vehicules, vente de pieces detachees.', ['Mecanique generale', 'Pieces detachees', 'Vente de vehicules']],
        ['Millennium Motors - Brazzaville', 'Brazzaville', 'Centre-ville', 'Avenue Foch', null,
            "Centre d'entretien multimarque : pieces d'origine et adaptables, lubrifiants et batteries pour tous types de vehicules.",
            ['Entretien / revision', 'Vidange', 'Batterie', 'Pieces detachees']],
        ['ETS Garage Auto Doukoure', 'Brazzaville', 'Moungali', 'Rue de Franceville (Poto-Poto)', '+242 069 35 89 57',
            'Garage de mecanique automobile a Moungali.', ['Mecanique generale']],
        ['Garage Auto Boss', 'Brazzaville', null, 'Avenue de la Tsieme', '+242 069 71 99 99',
            'Garage de mecanique automobile, avenue de la Tsieme.', ['Mecanique generale']],
        ['Garage La Solution Chez Champion', 'Brazzaville', 'Cite des 17', 'Rue Bomassa', '+242 010 35 45 61',
            'Garage de mecanique automobile a la Cite des 17.', ['Mecanique generale']],
        ['Garage Malga', 'Brazzaville', 'Moungali', 'Rue Nko', '+242 055 51 57 96',
            'Garage de mecanique automobile a Moungali.', ['Mecanique generale']],
        ['Business Service Technique', 'Brazzaville', 'Talangai', '22, rue Angola Libre', '+242 055 25 25 66',
            'Atelier de mecanique automobile a Talangai.', ['Mecanique generale']],
        ['Vulcanisation Bedeuf Malewa', 'Brazzaville', null, 'Route nationale 2', '+242 066 16 72 61',
            'Vulcanisation : reparation et montage de pneus.', ['Pneumatiques']],
        ['Etosha Centre Service Gardiennage Auto', 'Brazzaville', null, null, '+242 012 51 44 88',
            'Centre de services automobiles et gardiennage de vehicules.', ['Gardiennage', 'Entretien / revision']],
        // Pointe-Noire
        ['GMS Auto', 'Pointe-Noire', null, '06, rue Ndassa Mahouata', '+242 055 09 64 17',
            "Diagnostic au scanner, mecanique et electronique : allumage, injection, eclairage, ABS, alarmes, climatisation, antidemarrage.",
            ['Diagnostic', 'Electricite auto', 'Climatisation', 'Mecanique generale']],
        ['Eurotech Bosch Car Service', 'Pointe-Noire', null, 'Avenue Bitelika Ndombi', '+242 064 86 00 00',
            'Garage du reseau Bosch Car Service : entretien et reparation toutes marques.', ['Entretien / revision', 'Diagnostic', 'Mecanique generale']],
        ['Atelier Mecanique Garage 88', 'Pointe-Noire', null, "Rue de M'Bota, en face de la Clinique Netcare", '+242 055 27 58 44',
            "Diagnostic, entretien et reparation voitures et camions toutes marques, mecanique specialisee et vehicules industriels, travaux garantis.",
            ['Diagnostic', 'Entretien / revision', 'Mecanique generale', 'Poids lourds']],
        ['Garage MK Congo West Customs', 'Pointe-Noire', 'Songolo', 'Carrefour des dallettes Mont Sinai et Songolo', '+242 066 77 82 71',
            'Garage automobile a Pointe-Noire.', ['Mecanique generale']],
        ['RIMEX Congo', 'Pointe-Noire', null, '187, avenue Fayette Tchitembo', '+242 055 53 09 99',
            'Garage automobile a Pointe-Noire.', ['Mecanique generale']],
        ['Auto Expert', 'Pointe-Noire', null, null, '+242 050 60 00 02',
            'Garage automobile a Pointe-Noire.', ['Mecanique generale']],
        ['Garage MJP Automobile Plus', 'Pointe-Noire', null, null, '+242 065 53 63 64',
            'Garage automobile a Pointe-Noire.', ['Mecanique generale']],
        ['Garage Rouetech-Auto', 'Pointe-Noire', null, null, '+242 050 00 55 55',
            'Garage automobile a Pointe-Noire.', ['Mecanique generale']],
        ['Millennium Motors - Pointe-Noire', 'Pointe-Noire', null, "Rue de l'aeroport, rond-point des Chevaux", null,
            'Pieces detachees d\'origine et adaptables, lubrifiants et batteries pour tous types de vehicules.',
            ['Pieces detachees', 'Batterie', 'Vidange']],
        // Ouesso
        ['Garages de Kwamoutou', 'Ouesso', null, null, '+242 069 00 83 94',
            'Garage automobile a Ouesso (Sangha).', ['Mecanique generale']],
    ];

    public function up(): void
    {
        if (app()->environment('testing')) {
            return; // les tests creent leurs propres donnees
        }
        if (Schema::hasTable('businesses')) {
            foreach (self::DEALERS as $d) {
                $this->business('concessionnaire', $d);
            }
            foreach (self::GARAGES as $g) {
                $this->business('garage', $g);
            }
        }

        if (! Schema::hasTable('vehicles')) {
            return;
        }
        $now = now();
        foreach (json_decode(<<<'JSON'
[
 {
  "title": "Jetour T2 2025",
  "brand": "Jetour",
  "model": "T2",
  "version": "i-DM hybride rechargeable 4x4",
  "year": 2025,
  "mileage_km": 0,
  "fuel": "hybride",
  "transmission": "automatique",
  "category": "4x4",
  "color": "vert",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 T PHEV",
  "sale_price": 23500000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "GPS",
   "Camera de recul",
   "Toit ouvrant",
   "Sieges cuir",
   "4 roues motrices",
   "Demarrage sans cle"
  ],
  "description": "Jetour T2 (Traveller) hybride rechargeable, look baroudeur, 4 roues motrices. Ideal pour la ville et les pistes.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/9/97/Jetour_Traveller_001.jpg/960px-Jetour_Traveller_001.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/1e/Jetour_Traveller_011.jpg/960px-Jetour_Traveller_011.jpg",
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6b/Jetour_Traveller_012.jpg/960px-Jetour_Traveller_012.jpg"
  ]
 },
 {
  "title": "Jetour X70 2024",
  "brand": "Jetour",
  "model": "X70",
  "version": "1.5T Comfort 7 places",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "blanc",
  "doors": 5,
  "seats": 7,
  "engine": "1.5 L Turbo",
  "sale_price": 13900000,
  "city": "Pointe-Noire",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "GPS"
  ],
  "description": "SUV familial 7 places, bien equipe, consommation raisonnable.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/4/4f/Jetour_X70_006.jpg/960px-Jetour_X70_006.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cb/Jetour_X70_007.jpg/960px-Jetour_X70_007.jpg"
  ]
 },
 {
  "title": "Jetour Dashing 2024",
  "brand": "Jetour",
  "model": "Dashing",
  "version": "1.6T Premium",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "gris",
  "doors": 5,
  "seats": 5,
  "engine": "1.6 L Turbo",
  "sale_price": 15500000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "Toit ouvrant",
   "Demarrage sans cle"
  ],
  "description": "SUV au design moderne, grand ecran et aides a la conduite.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/1f/Jetour_Dashing_photo1.jpg/960px-Jetour_Dashing_photo1.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/17/Jetour_Dashing_006.jpg/960px-Jetour_Dashing_006.jpg"
  ]
 },
 {
  "title": "Chery Tiggo 7 2023",
  "brand": "Chery",
  "model": "Tiggo 7",
  "version": "1.5T Luxury",
  "year": 2023,
  "mileage_km": 22000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "rouge",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 L Turbo",
  "sale_price": 11800000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": true,
  "warranty_months": 6,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage"
  ],
  "description": "Tiggo 7 premiere main, entretien a jour, tres bon etat.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c3/Chery_Tiggo_7_II_IMG002.jpg/960px-Chery_Tiggo_7_II_IMG002.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c5/Chery_Tiggo_7_II_IMG005.jpg/960px-Chery_Tiggo_7_II_IMG005.jpg"
  ]
 },
 {
  "title": "Changan CS55 2023",
  "brand": "Changan",
  "model": "CS55",
  "version": "Plus 1.5T",
  "year": 2023,
  "mileage_km": 30000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "noir",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 L Turbo",
  "sale_price": 10900000,
  "city": "Pointe-Noire",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage"
  ],
  "description": "CS55 Plus confortable et silencieux, camera 360.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/2/21/Changan_CS55_facelift_001.jpg/960px-Changan_CS55_facelift_001.jpg",
  "gallery": []
 },
 {
  "title": "Geely Coolray 2024",
  "brand": "Geely",
  "model": "Coolray",
  "version": "1.5T Sport",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "bleu",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 L Turbo",
  "sale_price": 12500000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "Toit ouvrant"
  ],
  "description": "Petit SUV dynamique, boite a double embrayage.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/4/47/Moscow_-_2026_-_Geely_Coolray_compact_crossover.jpg/960px-Moscow_-_2026_-_Geely_Coolray_compact_crossover.jpg",
  "gallery": []
 },
 {
  "title": "Haval H6 2023",
  "brand": "Haval",
  "model": "H6",
  "version": "Hybrid HEV",
  "year": 2023,
  "mileage_km": 18000,
  "fuel": "hybride",
  "transmission": "automatique",
  "category": "suv",
  "color": "blanc",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 L Turbo hybride",
  "sale_price": 15900000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": true,
  "warranty_months": 12,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "GPS",
   "Sieges cuir"
  ],
  "description": "Haval H6 hybride, faible consommation, comme neuf.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/b/ba/Haval_H6_Supreme%2B_Edition_IMG001.jpg/960px-Haval_H6_Supreme%2B_Edition_IMG001.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c0/Haval_H6_Supreme%2B_Edition_IMG003.jpg/960px-Haval_H6_Supreme%2B_Edition_IMG003.jpg"
  ]
 },
 {
  "title": "BYD Atto 3 2024",
  "brand": "BYD",
  "model": "Atto 3",
  "version": "Extended Range",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "electrique",
  "transmission": "automatique",
  "category": "suv",
  "color": "gris",
  "doors": 5,
  "seats": 5,
  "engine": "Electrique 150 kW",
  "sale_price": 18500000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 72,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "GPS",
   "Demarrage sans cle"
  ],
  "description": "SUV 100 % electrique, autonomie environ 420 km. Recharge a domicile.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/7/78/BYD_Atto_3_1X7A6495.jpg/960px-BYD_Atto_3_1X7A6495.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/10/BYD_Atto_3_1X7A6494.jpg/960px-BYD_Atto_3_1X7A6494.jpg"
  ]
 },
 {
  "title": "MG ZS 2023",
  "brand": "MG",
  "model": "ZS",
  "version": "1.5 Luxury",
  "year": 2023,
  "mileage_km": 25000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "rouge",
  "doors": 5,
  "seats": 5,
  "engine": "1.5 L",
  "sale_price": 9500000,
  "city": "Pointe-Noire",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage"
  ],
  "description": "MG ZS economique et spacieux, ideal en ville.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/3/34/MG_ZS_Facelift_1X7A6412.jpg/960px-MG_ZS_Facelift_1X7A6412.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c5/MG_ZS_facelift_001.jpg/960px-MG_ZS_facelift_001.jpg"
  ]
 },
 {
  "title": "JAC T8 2022",
  "brand": "JAC",
  "model": "T8",
  "version": "2.0 TDi Double cabine",
  "year": 2022,
  "mileage_km": 60000,
  "fuel": "diesel",
  "transmission": "manuelle",
  "category": "pickup",
  "color": "blanc",
  "doors": 4,
  "seats": 5,
  "engine": "2.0 L Diesel",
  "sale_price": 11000000,
  "city": "Pointe-Noire",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth",
   "Vitres electriques"
  ],
  "description": "Pick-up robuste pour le travail, benne en bon etat.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/8/8b/JAC_T8_1X7A7303.jpg/960px-JAC_T8_1X7A7303.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d0/JAC_T8_1X7A7304.jpg/960px-JAC_T8_1X7A7304.jpg",
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/3/30/JAC_T8_1X7A7312.jpg/960px-JAC_T8_1X7A7312.jpg"
  ]
 },
 {
  "title": "Great Wall Wingle 2022",
  "brand": "Great Wall",
  "model": "Wingle",
  "version": "7 2.0 Diesel 4x4",
  "year": 2022,
  "mileage_km": 70000,
  "fuel": "diesel",
  "transmission": "manuelle",
  "category": "pickup",
  "color": "argent",
  "doors": 4,
  "seats": 5,
  "engine": "2.0 L Diesel",
  "sale_price": 9800000,
  "city": "Dolisie",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth",
   "Vitres electriques"
  ],
  "description": "Wingle 7 4x4, entretien regulier, pret pour les pistes.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/b/b5/Great_Wall_Wingle_7_002.jpg/960px-Great_Wall_Wingle_7_002.jpg",
  "gallery": []
 },
 {
  "title": "BAIC BJ40 2023",
  "brand": "BAIC",
  "model": "BJ40",
  "version": "2.0T 4WD",
  "year": 2023,
  "mileage_km": 20000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "4x4",
  "color": "vert",
  "doors": 5,
  "seats": 5,
  "engine": "2.0 L Turbo",
  "sale_price": 17500000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": true,
  "warranty_months": 12,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Camera de recul",
   "Bluetooth"
  ],
  "description": "Vrai tout-terrain, chassis echelle, comme neuf.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/a/ad/Beijing_BJ40_IMG003.jpg/960px-Beijing_BJ40_IMG003.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/7/74/Beijing_BJ40_IMG004.jpg/960px-Beijing_BJ40_IMG004.jpg"
  ]
 },
 {
  "title": "Foton Tunland 2023",
  "brand": "Foton",
  "model": "Tunland",
  "version": "2.0 Diesel 4x4",
  "year": 2023,
  "mileage_km": 0,
  "fuel": "diesel",
  "transmission": "manuelle",
  "category": "pickup",
  "color": "blanc",
  "doors": 4,
  "seats": 5,
  "engine": "2.0 L Diesel",
  "sale_price": 13500000,
  "city": "Pointe-Noire",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 24,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth",
   "Vitres electriques"
  ],
  "description": "Pick-up neuf, double cabine, garantie constructeur.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/Foton_Tunland_001.jpg/960px-Foton_Tunland_001.jpg",
  "gallery": []
 },
 {
  "title": "Isuzu D-Max 2024",
  "brand": "Isuzu",
  "model": "D-Max",
  "version": "3.0 Double cabine 4x4",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "diesel",
  "transmission": "manuelle",
  "category": "pickup",
  "color": "blanc",
  "doors": 4,
  "seats": 5,
  "engine": "3.0 L Diesel",
  "sale_price": 19500000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth",
   "Vitres electriques",
   "Regulateur de vitesse"
  ],
  "description": "Le pick-up increvable, ideal entreprises et chantiers.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/f/fc/2024_Isuzu_D-Max_%28third_generation%29_GIMS_2024_1X7A2055.jpg/960px-2024_Isuzu_D-Max_%28third_generation%29_GIMS_2024_1X7A2055.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/3/37/2024_Isuzu_D-Max_%28third_generation%29_GIMS_2024_1X7A2056.jpg/960px-2024_Isuzu_D-Max_%28third_generation%29_GIMS_2024_1X7A2056.jpg"
  ]
 },
 {
  "title": "Mazda CX-5 2019",
  "brand": "Mazda",
  "model": "CX-5",
  "version": "2.0 Skyactiv",
  "year": 2019,
  "mileage_km": 75000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "rouge",
  "doors": 5,
  "seats": 5,
  "engine": "2.0 L",
  "sale_price": 10500000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "Sieges cuir"
  ],
  "description": "CX-5 bien entretenu, finition soignee.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/4/43/Mazda_CX-5_Newground_1X7A6786.jpg/960px-Mazda_CX-5_Newground_1X7A6786.jpg",
  "gallery": []
 },
 {
  "title": "Toyota Fortuner 2021",
  "brand": "Toyota",
  "model": "Fortuner",
  "version": "2.8 GD-6 4x4 7 places",
  "year": 2021,
  "mileage_km": 65000,
  "fuel": "diesel",
  "transmission": "automatique",
  "category": "4x4",
  "color": "blanc",
  "doors": 5,
  "seats": 7,
  "engine": "2.8 L Diesel",
  "sale_price": 22000000,
  "city": "Pointe-Noire",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": 6,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "4 roues motrices"
  ],
  "description": "Fortuner 7 places, carnet d'entretien, pneus recents.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/14/2021_Toyota_Fortuner_SRZ_2.7_4x2_%28Indonesia%29_front_view_01.jpg/960px-2021_Toyota_Fortuner_SRZ_2.7_4x2_%28Indonesia%29_front_view_01.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/2/23/2021_Toyota_Fortuner_SRZ_2.7_4x2_%28Indonesia%29_front_view_02.jpg/960px-2021_Toyota_Fortuner_SRZ_2.7_4x2_%28Indonesia%29_front_view_02.jpg"
  ]
 },
 {
  "title": "Mitsubishi Pajero Sport 2020",
  "brand": "Mitsubishi",
  "model": "Pajero Sport",
  "version": "2.4 DI-D 4WD",
  "year": 2020,
  "mileage_km": 80000,
  "fuel": "diesel",
  "transmission": "automatique",
  "category": "4x4",
  "color": "gris",
  "doors": 5,
  "seats": 7,
  "engine": "2.4 L Diesel",
  "sale_price": 17000000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "4 roues motrices"
  ],
  "description": "Pajero Sport 7 places, tres bon etat general.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/1/19/Mitsubishi_Pajero_Sport_%283rd_generation%29_1X7A0409.jpg/960px-Mitsubishi_Pajero_Sport_%283rd_generation%29_1X7A0409.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/b/bf/Mitsubishi_Pajero_Sport_%283rd_generation%29_1X7A0410.jpg/960px-Mitsubishi_Pajero_Sport_%283rd_generation%29_1X7A0410.jpg"
  ]
 },
 {
  "title": "Nissan Navara 2020",
  "brand": "Nissan",
  "model": "Navara",
  "version": "2.3 dCi Double cabine",
  "year": 2020,
  "mileage_km": 90000,
  "fuel": "diesel",
  "transmission": "manuelle",
  "category": "pickup",
  "color": "noir",
  "doors": 4,
  "seats": 5,
  "engine": "2.3 L Diesel",
  "sale_price": 12500000,
  "city": "Pointe-Noire",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth",
   "Vitres electriques"
  ],
  "description": "Navara double cabine, ideal travail et loisirs.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/e/e7/20200512_Nissan_Navara_01.jpg/960px-20200512_Nissan_Navara_01.jpg",
  "gallery": [
   "https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d9/20200512_Nissan_Navara_02.jpg/960px-20200512_Nissan_Navara_02.jpg"
  ]
 },
 {
  "title": "Suzuki Jimny 2023",
  "brand": "Suzuki",
  "model": "Jimny",
  "version": "1.5 AllGrip",
  "year": 2023,
  "mileage_km": 15000,
  "fuel": "essence",
  "transmission": "manuelle",
  "category": "4x4",
  "color": "jaune",
  "doors": 3,
  "seats": 4,
  "engine": "1.5 L",
  "sale_price": 13500000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": true,
  "warranty_months": 12,
  "options": [
   "Climatisation",
   "4 roues motrices",
   "Bluetooth"
  ],
  "description": "Petit 4x4 culte, agile en ville et en brousse.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/e/e8/Suzuki_Jimny%2C_Iceland%2C_20230430_1704_3701.jpg/960px-Suzuki_Jimny%2C_Iceland%2C_20230430_1704_3701.jpg",
  "gallery": []
 },
 {
  "title": "Hyundai Creta 2022",
  "brand": "Hyundai",
  "model": "Creta",
  "version": "1.6 GLS",
  "year": 2022,
  "mileage_km": 40000,
  "fuel": "essence",
  "transmission": "automatique",
  "category": "suv",
  "color": "blanc",
  "doors": 5,
  "seats": 5,
  "engine": "1.6 L",
  "sale_price": 9900000,
  "city": "Brazzaville",
  "condition": "occasion",
  "first_owner": false,
  "warranty_months": null,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage"
  ],
  "description": "Creta automatique, economique et fiable.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/7/7b/HYUNDAI_CRETA_%2C_iX25_%28SU2%29_China.jpg/960px-HYUNDAI_CRETA_%2C_iX25_%28SU2%29_China.jpg",
  "gallery": []
 },
 {
  "title": "Kia Sorento 2024",
  "brand": "Kia",
  "model": "Sorento",
  "version": "1.6 T-GDi Hybrid 7 places",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "hybride",
  "transmission": "automatique",
  "category": "suv",
  "color": "noir",
  "doors": 5,
  "seats": 7,
  "engine": "1.6 L Turbo hybride",
  "sale_price": 28000000,
  "city": "Brazzaville",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 60,
  "options": [
   "Climatisation",
   "Camera de recul",
   "Bluetooth",
   "Regulateur de vitesse",
   "Jantes alliage",
   "GPS",
   "Sieges cuir",
   "Toit ouvrant"
  ],
  "description": "Grand SUV hybride 7 places, haut de gamme.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/3/39/2024_Kia_Sorento_%28MQ4%29_Ditzingen_Mobil_IMG_9803.jpg/960px-2024_Kia_Sorento_%28MQ4%29_Ditzingen_Mobil_IMG_9803.jpg",
  "gallery": []
 },
 {
  "title": "Dacia Duster 2024",
  "brand": "Dacia",
  "model": "Duster",
  "version": "1.3 TCe Journey",
  "year": 2024,
  "mileage_km": 0,
  "fuel": "essence",
  "transmission": "manuelle",
  "category": "suv",
  "color": "vert",
  "doors": 5,
  "seats": 5,
  "engine": "1.3 L Turbo",
  "sale_price": 11500000,
  "city": "Pointe-Noire",
  "condition": "neuf",
  "first_owner": false,
  "warranty_months": 36,
  "options": [
   "Climatisation",
   "Bluetooth",
   "Camera de recul"
  ],
  "description": "Nouveau Duster, simple et robuste, excellent rapport prix / equipement.",
  "image": "https://thumb.wikimedia.org/wikipedia/commons/thumb/9/9b/Dacia_Duster_III_IMG_8961.jpg/960px-Dacia_Duster_III_IMG_8961.jpg",
  "gallery": []
 }
]
JSON, true) as $v) {
            if (DB::table('vehicles')->where('title', $v['title'])->where('offer_type', 'vente')->exists()) {
                continue;
            }
            DB::table('vehicles')->insert(array_merge($v, [
                'offer_type' => 'vente',
                'slug' => Str::slug($v['title']).'-'.Str::lower(Str::random(5)),
                'options' => json_encode($v['options']),
                'gallery' => $v['gallery'] ? json_encode($v['gallery']) : null,
                'currency' => 'XAF',
                'country' => 'CG',
                'is_active' => true,
                'status' => 'published',
                'is_demo' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('businesses')) {
            $slugs = array_map(fn ($b) => Str::slug($b[0]), array_merge(self::DEALERS, self::GARAGES));
            DB::table('businesses')->whereIn('slug', $slugs)->delete();
        }
    }

    /** @param array{0:string,1:string,2:?string,3:?string,4:?string,5:string,6:array} $b */
    private function business(string $category, array $b): void
    {
        [$name, $city, $district, $address, $phone, $description, $services] = $b;
        $slug = Str::slug($name);
        if (DB::table('businesses')->where('slug', $slug)->exists()) {
            return;
        }
        DB::table('businesses')->insert([
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'country' => 'CG',
            'phone' => $phone,
            'description' => $description,
            'services' => json_encode($services, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'is_featured' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
