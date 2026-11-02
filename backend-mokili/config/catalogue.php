<?php

/*
|--------------------------------------------------------------------------
| Rubriques et filtres MOKILI TOUR (site web + API de l'app mobile)
|--------------------------------------------------------------------------
| Une seule source pour les libelles : le site, le back-office, l'espace
| partenaire et l'API (/api/catalogue) lisent ces listes.
*/

return [

    // 15 departements depuis les lois d'octobre 2024.
    'departments' => [
        'Brazzaville', 'Pointe-Noire', 'Bouenza', 'Congo-Oubangui', 'Cuvette', 'Cuvette-Ouest',
        'Djoue-Lefini', 'Kouilou', 'Lekoumou', 'Likouala', 'Niari', 'Nkeni-Alima', 'Plateaux',
        'Pool', 'Sangha',
    ],

    'voyage' => [
        // Circuits : moyens de transport.
        'transport_modes' => [
            'avion' => 'Avion',
            'bus' => 'Bus / car',
            'train' => 'Train',
            'bateau' => 'Bateau',
            'canot' => 'Canot rapide / pirogue',
            'voiture' => 'Voiture / 4x4',
            'combine' => 'Combine (plusieurs transports)',
        ],
        // Circuits : dans une ville, entre departements, ou vers l'etranger.
        'trip_scopes' => [
            'urbain' => 'En ville',
            'interdepartemental' => 'Inter-departements',
            'international' => 'International',
        ],
    ],

    'logement' => [
        'property_types' => [
            'villa' => 'Villa',
            'hotel' => 'Hotel',
            'appartement' => 'Appartement',
        ],
    ],

    'voiture' => [
        'offer_types' => [
            'location' => 'Location',
            'vente' => 'Vente',
            'mecanique' => 'Mecanique',
        ],
        'mechanic_services' => [
            'Vidange', 'Diagnostic', 'Freinage', 'Pneumatiques', 'Climatisation', 'Carrosserie',
            'Electricite auto', 'Depannage / remorquage',
        ],
    ],

    // Divertissement devient "Tourisme" (slug technique inchange).
    'tourisme' => [
        'label' => 'Tourisme',
        'tabs' => [
            'concerts' => ['label' => 'Concerts & spectacles', 'source' => 'events', 'categories' => ['concert', 'spectacle']],
            'cinema' => ['label' => 'Cinema', 'source' => 'events', 'categories' => ['cinema']],
            'parcs' => ['label' => 'Parcs', 'source' => 'parks'],
            'night_club' => ['label' => 'Night club', 'source' => 'businesses', 'categories' => ['night_club']],
            'restaurant' => ['label' => 'Restaurant', 'source' => 'businesses', 'categories' => ['restaurant']],
            'bar_lounge' => ['label' => 'Bar lounge', 'source' => 'businesses', 'categories' => ['bar_lounge']],
            'visite' => ['label' => 'Visite', 'source' => 'businesses', 'categories' => ['site_touristique']],
        ],
        'event_categories' => [
            'concert' => 'Concert',
            'spectacle' => 'Spectacle',
            'cinema' => 'Cinema',
            'sport' => 'Sport',
            'soiree' => 'Soiree',
        ],
    ],

    // Petites annonces entre particuliers et pros (modele Leboncoin /
    // Facebook Marketplace).
    'marketplace' => [
        'listing_types' => [
            'vente' => 'Je vends',
            'recherche' => 'Je recherche',
        ],
        'categories' => [
            'vehicules' => 'Vehicules & pieces',
            'immobilier' => 'Immobilier',
            'telephones' => 'Telephones & tablettes',
            'electronique' => 'Electronique & informatique',
            'maison' => 'Maison & electromenager',
            'mode' => 'Mode & beaute',
            'enfants' => 'Bebe & enfants',
            'loisirs' => 'Loisirs & sport',
            'materiaux' => 'Materiaux & BTP',
            'agriculture' => 'Alimentation & agriculture',
            'emploi' => 'Emploi & services',
            'autres' => 'Autres',
        ],
        'conditions' => [
            'neuf' => 'Neuf',
            'occasion' => 'Occasion',
        ],
    ],

    'fret' => [
        'scopes' => [
            'national' => 'National',
            'international' => 'International',
        ],
        'modes' => [
            'mer' => 'Maritime',
            'air' => 'Aerien',
            'route' => 'Routier',
        ],
        'package_types' => [
            'documents' => 'Documents',
            'marchandise' => 'Marchandise',
            'vehicule' => 'Vehicule',
            'conteneur' => 'Conteneur',
            'autre' => 'Autres',
        ],
    ],
];
