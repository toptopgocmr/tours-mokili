<?php

/*
| Photos d'illustration des societes partenaires (Voiture) tant que la
| societe n'a pas fourni ses photos officielles (/admin/partenaires : une
| photo deposee remplace automatiquement l'illustration). Wikimedia Commons,
| licences libres (CC BY-SA). Affichees avec la mention "Photo d'illustration".
*/

$w = 'https://thumb.wikimedia.org/wikipedia/commons/thumb/';
// Fichier Commons par son nom (redirection vers une vignette 640 px).
$f = fn (string $file) => 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($file).'?width=640';

return [
    // Concessionnaires : photo officielle d'un vehicule de la marque distribuee
    // (sinon config/vehicle_photos.php), cle = service de la societe.
    'brands' => [
        'Volvo Trucks' => $w.'3/3e/Volvo_FH_500_in_Frauenfeld.jpg/960px-Volvo_FH_500_in_Frauenfeld.jpg',
        'Camions' => $w.'3/3e/Volvo_FH_500_in_Frauenfeld.jpg/960px-Volvo_FH_500_in_Frauenfeld.jpg',
    ],
    'concessionnaire_default' => $w.'a/a2/Cars_at_parking_slots_in_Maiduguri_Borno_State_%285%29.jpg/960px-Cars_at_parking_slots_in_Maiduguri_Borno_State_%285%29.jpg',

    // Garages : selon la prestation principale.
    'garage_services' => [
        'Pneumatiques' => $w.'d/d6/Ghanaian_Tyre_Repairer_01.jpg/960px-Ghanaian_Tyre_Repairer_01.jpg',
        'Depannage / remorquage' => $w.'b/b6/DongFeng_tow_truck%2C_Korle-Klottey_%28P1090871%29.jpg/960px-DongFeng_tow_truck%2C_Korle-Klottey_%28P1090871%29.jpg',
        'Poids lourds' => $w.'3/3e/Volvo_FH_500_in_Frauenfeld.jpg/960px-Volvo_FH_500_in_Frauenfeld.jpg',
    ],
    // Tourisme : photo d'illustration par categorie (en attendant les photos
    // officielles). Choisies visuellement, licences libres.
    'categories' => [
        // Parkings, gardiennage et lavage auto.
        'parking' => [
            $w.'a/a2/Cars_at_parking_slots_in_Maiduguri_Borno_State_%285%29.jpg/960px-Cars_at_parking_slots_in_Maiduguri_Borno_State_%285%29.jpg',
            $w.'8/85/Car_park%2C_Accra_%28IMG_20230201_143906_1%29.jpg/960px-Car_park%2C_Accra_%28IMG_20230201_143906_1%29.jpg',
        ],
        'restaurant' => [
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/65/Plats_congolais.jpg/960px-Plats_congolais.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/ad/Poisson_brais%C3%A9%2C_accompagn%C3%A9_d%E2%80%99aloco.jpg/960px-Poisson_brais%C3%A9%2C_accompagn%C3%A9_d%E2%80%99aloco.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/2/2f/Formation_de_photographie_de_WLA_2025_en_RCongo_07.jpg/960px-Formation_de_photographie_de_WLA_2025_en_RCongo_07.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/b/b9/Mon_propre_travail_033.jpg/960px-Mon_propre_travail_033.jpg',
        ],
        'bar_lounge' => [
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/9b/Lounge_bar_in_Ouagadougou.jpg/960px-Lounge_bar_in_Ouagadougou.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/57/Madonna_Inn_Silver_Bar_Cocktail_Lounge_01.jpg/960px-Madonna_Inn_Silver_Bar_Cocktail_Lounge_01.jpg',
        ],
        'night_club' => [
            'https://upload.wikimedia.org/wikipedia/commons/3/35/Pure_Nightclub_Las_Vegas_Dance_Floor.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/82/Charlie_Nightclub_Munich_Dance_Floor_1.jpg/960px-Charlie_Nightclub_Munich_Dance_Floor_1.jpg',
        ],
        'cinema' => [
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/93/Salle_de_spectacle_CanalOlympia_Wologu%C3%A8d%C3%A9_%C3%A0_Cotonou.jpg/960px-Salle_de_spectacle_CanalOlympia_Wologu%C3%A8d%C3%A9_%C3%A0_Cotonou.jpg',
        ],
        'salle_spectacle' => [
            'https://upload.wikimedia.org/wikipedia/commons/8/8f/Brazzaville_NN.jpg',
            'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/17/Concert_de_cloture_du_FEMUA15_%C3%A0_Bouak%C3%A9_53.jpg/960px-Concert_de_cloture_du_FEMUA15_%C3%A0_Bouak%C3%A9_53.jpg',
        ],
        // Annuaire : hotels, transport, commerce (illustrations par domaine).
        'hotel' => [
            $f('Hotel_Room_with_queen_size_bed.jpg'),
            $f('Bed_in_hotel_room_3.jpg'),
            $f('Hotel_room_interior_at_hotel_Radisson_Blu_Oulu.jpg'),
            $f('UMass_Hotel_bed.JPG'),
        ],
        'compagnie_aerienne' => [$f('Aeroport_maya-maya.jpg')],
        'transport_routier' => [
            $f('Transport_terrestre_interurbain_par_bus_au_Cameroun_12.jpg'),
            $f('Transport_terrestre_interurbain_par_bus_au_Cameroun_10.jpg'),
        ],
        'taxi_vtc' => [$f('Le_pont_du_Djoué.jpg'), $f('Taxi_de_Dolisie.jpg')],
        'canot_rapide' => [$f('Pirogue_sur_le_fleuve_Congo.jpg')],
        'transport_ferroviaire' => [$f('P100813_1736_un_train_du_chemin_de_fer_congo_ocean.jpg')],
        'supermarche' => [
            $f('Interior_of_Supermarket_in_Japan_01.jpg'),
            $f('SAS_Supermarket_-_interior-_4.jpg'),
        ],
        'ecommerce' => [
            $f('SAS_Supermarket_-_interior-_4.jpg'),
            $f('Interior_of_Supermarket_in_Japan_01.jpg'),
        ],
    ],

    // Photo propre a une societe, reperee par un mot de son nom (avion de la
    // compagnie aux couleurs de la marque, etc.). Prioritaire sur la categorie.
    'name_keywords' => [
        'ethiopian' => $f('Boeing_787-8_Ethiopian_Airlines.jpg'),
        'air france' => $f('Boeing_777_F-GSPF_Air_France.jpg'),
        'rwandair' => $f('Airbus_A330-243_RwandAir.jpg'),
        'royal air maroc' => $f('Boeing_737-800_Royal_Air_Maroc_(RAM)_CN-RGF_-_MSN_36826_3773_(10276084206).jpg'),
        'asky' => $f('ETAOK-DGAA.jpg'),
        'taag' => $f('D2-TEF_Boeing_777_TAAG_Angola_Airlines_(8145090445).jpg'),
        'ecair' => $f('ECAir_Boeing_737-700_at_Zurich_Airport.jpg'),
        'cfco' => $f('P100813_1736_un_train_du_chemin_de_fer_congo_ocean.jpg'),
    ],

    // Dernier recours (domaines de l'annuaire sans photo dediee) : la ville.
    'fallback_cities' => [
        'Pointe-Noire' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/38/Pointe-Noire_%28C%C3%B4te_sauvage_PN%29_05.jpg/960px-Pointe-Noire_%28C%C3%B4te_sauvage_PN%29_05.jpg',
    ],
    'fallback' => $f('Les_lieux_de_Brazzaville_03.jpg'),

    // Sites touristiques sans photo : selon un mot du nom, sinon la ville.
    'site_keywords' => [
        'ferme' => 'https://upload.wikimedia.org/wikipedia/commons/9/96/FERME_VOKA_CON-BR1.jpg',
    ],
    'site_cities' => [
        'Brazzaville' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/03/Corniche_de_Brazzaville_Congo.jpg/960px-Corniche_de_Brazzaville_Congo.jpg',
        'Pointe-Noire' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/38/Pointe-Noire_%28C%C3%B4te_sauvage_PN%29_05.jpg/960px-Pointe-Noire_%28C%C3%B4te_sauvage_PN%29_05.jpg',
    ],

    // Mecanique generale : mecaniciennes en atelier (serie "Femme mecanicienne").
    'garage_default' => [
        'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a6/Femme_mecanicienne5.jpg/960px-Femme_mecanicienne5.jpg',
        'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/90/Femme_mecanicienne33.jpg/960px-Femme_mecanicienne33.jpg',
    ],
];
