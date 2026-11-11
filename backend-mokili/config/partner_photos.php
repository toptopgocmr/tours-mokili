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
    // Mecanique generale : mecaniciennes en atelier (serie "Femme mecanicienne").
    'garage_default' => [
        $w.'a/ad/Femme_mecanicienne12.jpg/960px-Femme_mecanicienne12.jpg',
        $w.'a/a6/Femme_mecanicienne5.jpg/960px-Femme_mecanicienne5.jpg',
        $w.'2/2b/Femme_mecanicienne18.jpg/960px-Femme_mecanicienne18.jpg',
        $w.'9/90/Femme_mecanicienne33.jpg/960px-Femme_mecanicienne33.jpg',
        $w.'c/c9/Femme_mecanicienne17.jpg/960px-Femme_mecanicienne17.jpg',
        $w.'7/7d/Femme_mecanicienne7.jpg/960px-Femme_mecanicienne7.jpg',
    ],
];
