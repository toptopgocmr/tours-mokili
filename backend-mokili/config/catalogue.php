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
        // Annonces de vente : listes proposees aux vendeurs et aux acheteurs.
        'brands' => [
            'Toyota' => ['Corolla', 'Yaris', 'Camry', 'RAV4', 'Land Cruiser', 'Land Cruiser Prado', 'Hilux', 'Fortuner', 'Rush', 'Hiace', 'Avensis', 'Highlander', 'C-HR'],
            'Hyundai' => ['i10', 'i20', 'Accent', 'Elantra', 'Tucson', 'Santa Fe', 'Creta', 'H1'],
            'Kia' => ['Picanto', 'Rio', 'Cerato', 'Sportage', 'Sorento', 'Seltos'],
            'Nissan' => ['Micra', 'Sunny', 'Almera', 'Qashqai', 'X-Trail', 'Patrol', 'Navara', 'Pathfinder'],
            'Mitsubishi' => ['L200', 'Pajero', 'Pajero Sport', 'Outlander', 'ASX'],
            'Suzuki' => ['Alto', 'Swift', 'Dzire', 'Vitara', 'Jimny', 'Ertiga'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'HR-V', 'Fit'],
            'Mazda' => ['Mazda 2', 'Mazda 3', 'Mazda 6', 'CX-3', 'CX-5', 'BT-50'],
            'Ford' => ['Fiesta', 'Focus', 'Ranger', 'Everest', 'Explorer', 'EcoSport'],
            'Peugeot' => ['208', '301', '308', '508', '2008', '3008', '5008', 'Partner', 'Expert'],
            'Renault' => ['Clio', 'Logan', 'Sandero', 'Megane', 'Duster', 'Koleos', 'Kangoo'],
            'Volkswagen' => ['Polo', 'Golf', 'Passat', 'Jetta', 'Tiguan', 'Touareg', 'Amarok'],
            'Mercedes-Benz' => ['Classe A', 'Classe C', 'Classe E', 'Classe S', 'GLA', 'GLC', 'GLE', 'Classe G', 'Sprinter'],
            'BMW' => ['Serie 1', 'Serie 3', 'Serie 5', 'X1', 'X3', 'X5', 'X6'],
            'Audi' => ['A3', 'A4', 'A6', 'Q3', 'Q5', 'Q7'],
            'Lexus' => ['IS', 'ES', 'RX', 'GX', 'LX', 'NX'],
            'Land Rover' => ['Defender', 'Discovery', 'Range Rover', 'Range Rover Sport', 'Range Rover Evoque'],
            'Isuzu' => ['D-Max', 'MU-X', 'NPR'],
            'Chevrolet' => ['Spark', 'Aveo', 'Cruze', 'Captiva', 'Tahoe'],
            'Jeep' => ['Renegade', 'Compass', 'Cherokee', 'Grand Cherokee', 'Wrangler'],
            'Chery' => ['Tiggo 2', 'Tiggo 4', 'Tiggo 7', 'Tiggo 8'],
            'Geely' => ['Coolray', 'Emgrand', 'Okavango', 'Tugella'],
            'Haval' => ['Jolion', 'H6', 'H9'],
            'Autre' => [],
        ],
        'colors' => [
            'blanc' => ['Blanc', '#ffffff'], 'noir' => ['Noir', '#111827'], 'gris' => ['Gris', '#9ca3af'], 'argent' => ['Argent', '#d1d5db'],
            'bleu' => ['Bleu', '#1d4ed8'], 'rouge' => ['Rouge', '#dc2626'], 'vert' => ['Vert', '#15803d'], 'beige' => ['Beige', '#e7d8b8'],
            'marron' => ['Marron', '#78350f'], 'jaune' => ['Jaune', '#facc15'], 'orange' => ['Orange', '#f97316'], 'autre' => ['Autre', 'linear-gradient(135deg,#f472b6,#60a5fa)'],
        ],
        'conditions' => ['occasion' => 'Occasion', 'neuf' => 'Neuf', 'reconditionne' => 'Reconditionne'],
        'fuels' => ['essence' => 'Essence', 'diesel' => 'Diesel', 'hybride' => 'Hybride', 'electrique' => 'Electrique', 'gpl' => 'GPL'],
        'categories' => [
            'citadine' => 'Citadine', 'berline' => 'Berline', 'suv' => 'SUV', '4x4' => '4x4', 'pickup' => 'Pick-up',
            'monospace' => 'Monospace', 'coupe' => 'Coupe / Cabriolet', 'utilitaire' => 'Utilitaire', 'luxe' => 'Luxe',
        ],
        'options' => [
            'Climatisation', 'GPS', 'Camera de recul', 'Radar de recul', 'Bluetooth', 'Toit ouvrant', 'Sieges cuir',
            'Jantes alliage', 'Regulateur de vitesse', 'Demarrage sans cle', '4 roues motrices', 'Vitres electriques',
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
