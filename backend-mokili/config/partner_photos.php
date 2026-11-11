<?php

/*
| Photos d'illustration des societes partenaires (Voiture) tant que la
| societe n'a pas fourni ses photos officielles (/admin/partenaires : une
| photo deposee remplace automatiquement l'illustration). Wikimedia Commons,
| licences libres (CC BY-SA). Affichees avec la mention "Photo d'illustration".
*/

$w = 'https://thumb.wikimedia.org/wikipedia/commons/thumb/';

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
    ],

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
