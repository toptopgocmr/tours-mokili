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
            'Abarth' => ['500', '595', '695', '124 Spider'],
            'Acura' => ['ILX', 'TLX', 'RDX', 'MDX', 'Integra'],
            'Alfa Romeo' => ['Giulietta', 'Giulia', 'Stelvio', 'Tonale', 'MiTo'],
            'Aston Martin' => ['Vantage', 'DB11', 'DBS', 'DBX'],
            'Audi' => ['A1', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'e-tron', 'TT', 'R8'],
            'BAIC' => ['X25', 'X35', 'X55', 'BJ40'],
            'Bentley' => ['Continental GT', 'Flying Spur', 'Bentayga'],
            'BMW' => ['Serie 1', 'Serie 2', 'Serie 3', 'Serie 4', 'Serie 5', 'Serie 7', 'X1', 'X2', 'X3', 'X4', 'X5', 'X6', 'X7', 'Z4', 'i3', 'i4', 'iX'],
            'BYD' => ['F3', 'Atto 3', 'Dolphin', 'Seal', 'Han', 'Tang', 'Song'],
            'Cadillac' => ['Escalade', 'CT4', 'CT5', 'XT4', 'XT5', 'XT6'],
            'Changan' => ['CS35', 'CS55', 'CS75', 'CS85', 'Alsvin', 'Hunter'],
            'Chery' => ['QQ', 'Arrizo 5', 'Tiggo 2', 'Tiggo 4', 'Tiggo 7', 'Tiggo 8'],
            'Chevrolet' => ['Spark', 'Aveo', 'Cruze', 'Malibu', 'Captiva', 'Trax', 'Equinox', 'Tahoe', 'Suburban', 'Silverado', 'Colorado'],
            'Chrysler' => ['300', 'Pacifica', 'Voyager'],
            'Citroen' => ['C1', 'C3', 'C3 Aircross', 'C4', 'C5 Aircross', 'Berlingo', 'Jumpy', 'Jumper', 'C-Elysee'],
            'Cupra' => ['Born', 'Formentor', 'Leon', 'Ateca'],
            'Dacia' => ['Sandero', 'Logan', 'Duster', 'Jogger', 'Spring', 'Lodgy', 'Dokker'],
            'Daewoo' => ['Matiz', 'Lanos', 'Nubira'],
            'Daihatsu' => ['Terios', 'Sirion', 'Gran Max', 'Rocky'],
            'Dodge' => ['Charger', 'Challenger', 'Durango', 'Journey', 'RAM 1500'],
            'DS' => ['DS 3', 'DS 4', 'DS 7', 'DS 9'],
            'Ferrari' => ['Roma', 'Portofino', '296', 'F8', 'SF90', 'Purosangue'],
            'Fiat' => ['500', 'Panda', 'Tipo', 'Punto', 'Doblo', 'Fiorino', 'Ducato', 'Strada'],
            'Ford' => ['Ka', 'Fiesta', 'Focus', 'Fusion', 'Mondeo', 'Puma', 'Kuga', 'EcoSport', 'Edge', 'Explorer', 'Everest', 'Expedition', 'Ranger', 'F-150', 'Transit', 'Mustang'],
            'Foton' => ['Tunland', 'View', 'Aumark', 'Gratour'],
            'GAC' => ['GS3', 'GS4', 'GS8', 'Emkoo'],
            'Geely' => ['Coolray', 'Emgrand', 'Okavango', 'Tugella', 'Azkarra', 'Monjaro'],
            'Genesis' => ['G70', 'G80', 'GV70', 'GV80'],
            'GMC' => ['Sierra', 'Yukon', 'Acadia', 'Terrain'],
            'Great Wall' => ['Wingle', 'Poer', 'Steed'],
            'Haval' => ['Jolion', 'H2', 'H6', 'H9', 'Dargo'],
            'Honda' => ['Jazz', 'Fit', 'City', 'Civic', 'Accord', 'HR-V', 'CR-V', 'Pilot', 'BR-V', 'Odyssey'],
            'Hummer' => ['H2', 'H3', 'EV'],
            'Hyundai' => ['i10', 'i20', 'i30', 'Accent', 'Elantra', 'Sonata', 'Venue', 'Kona', 'Creta', 'Tucson', 'Santa Fe', 'Palisade', 'Staria', 'H1', 'Ioniq 5'],
            'Infiniti' => ['Q50', 'QX50', 'QX60', 'QX80'],
            'Isuzu' => ['D-Max', 'MU-X', 'NPR', 'NQR'],
            'Iveco' => ['Daily', 'Eurocargo', 'Trakker'],
            'JAC' => ['J7', 'S3', 'S4', 'T6', 'T8'],
            'Jaguar' => ['XE', 'XF', 'F-Pace', 'E-Pace', 'I-Pace', 'F-Type'],
            'Jeep' => ['Renegade', 'Compass', 'Cherokee', 'Grand Cherokee', 'Wrangler', 'Gladiator'],
            'Jetour' => ['X70', 'X90', 'Dashing', 'T2'],
            'Kia' => ['Picanto', 'Rio', 'Pegas', 'Cerato', 'K5', 'Stonic', 'Seltos', 'Soul', 'Sportage', 'Sorento', 'Carnival', 'Telluride', 'Niro', 'EV6'],
            'Lada' => ['Niva', 'Granta', 'Vesta', 'Largus'],
            'Lamborghini' => ['Huracan', 'Urus', 'Revuelto'],
            'Land Rover' => ['Defender', 'Discovery', 'Discovery Sport', 'Range Rover', 'Range Rover Sport', 'Range Rover Velar', 'Range Rover Evoque'],
            'Lexus' => ['IS', 'ES', 'LS', 'UX', 'NX', 'RX', 'GX', 'LX', 'LC'],
            'Lincoln' => ['Navigator', 'Aviator', 'Nautilus', 'Corsair'],
            'Mahindra' => ['Scorpio', 'XUV500', 'XUV700', 'Thar', 'Pik Up', 'Bolero'],
            'MAN' => ['TGE', 'TGL', 'TGM', 'TGS'],
            'Maserati' => ['Ghibli', 'Levante', 'Quattroporte', 'Grecale'],
            'Mazda' => ['Mazda 2', 'Mazda 3', 'Mazda 6', 'CX-3', 'CX-30', 'CX-5', 'CX-60', 'CX-9', 'BT-50', 'MX-5'],
            'McLaren' => ['GT', '720S', 'Artura'],
            'Mercedes-Benz' => ['Classe A', 'Classe B', 'Classe C', 'Classe E', 'Classe S', 'CLA', 'CLS', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'Classe G', 'Classe V', 'Vito', 'Sprinter', 'EQE', 'EQS', 'AMG GT'],
            'MG' => ['MG3', 'MG5', 'ZS', 'HS', 'RX5', 'MG4', 'Marvel R'],
            'Mini' => ['Cooper', 'Clubman', 'Countryman'],
            'Mitsubishi' => ['Mirage', 'Attrage', 'Lancer', 'ASX', 'Eclipse Cross', 'Outlander', 'Pajero', 'Pajero Sport', 'L200', 'Triton', 'Canter', 'Fuso'],
            'Nissan' => ['Micra', 'Sunny', 'Almera', 'Sentra', 'Altima', 'Juke', 'Kicks', 'Qashqai', 'X-Trail', 'Murano', 'Pathfinder', 'Patrol', 'Navara', 'NP200', 'Urvan', 'Leaf'],
            'Opel' => ['Corsa', 'Astra', 'Insignia', 'Crossland', 'Grandland', 'Mokka', 'Vivaro', 'Movano'],
            'Peugeot' => ['107', '108', '206', '207', '208', '301', '308', '407', '508', '2008', '3008', '5008', 'Partner', 'Rifter', 'Expert', 'Boxer', 'Landtrek'],
            'Porsche' => ['911', '718', 'Macan', 'Cayenne', 'Panamera', 'Taycan'],
            'Proton' => ['Saga', 'Persona', 'X50', 'X70'],
            'RAM' => ['1500', '2500', 'ProMaster'],
            'Renault' => ['Twingo', 'Clio', 'Symbol', 'Logan', 'Sandero', 'Megane', 'Talisman', 'Captur', 'Kadjar', 'Arkana', 'Austral', 'Koleos', 'Duster', 'Kwid', 'Kangoo', 'Trafic', 'Master', 'Alaskan'],
            'Rolls-Royce' => ['Ghost', 'Phantom', 'Cullinan', 'Wraith'],
            'Seat' => ['Ibiza', 'Leon', 'Arona', 'Ateca', 'Tarraco'],
            'Skoda' => ['Fabia', 'Scala', 'Octavia', 'Superb', 'Kamiq', 'Karoq', 'Kodiaq', 'Enyaq'],
            'Smart' => ['Fortwo', 'Forfour', '#1'],
            'SsangYong' => ['Tivoli', 'Korando', 'Rexton', 'Musso'],
            'Subaru' => ['Impreza', 'Legacy', 'XV', 'Forester', 'Outback', 'WRX'],
            'Suzuki' => ['Alto', 'Celerio', 'Swift', 'Dzire', 'Baleno', 'Ciaz', 'Ignis', 'S-Presso', 'Vitara', 'Grand Vitara', 'Jimny', 'Ertiga', 'XL7', 'Carry'],
            'Tata' => ['Indica', 'Nexon', 'Safari', 'Xenon', 'Ace'],
            'Tesla' => ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck'],
            'Toyota' => ['Aygo', 'Yaris', 'Vitz', 'Etios', 'Corolla', 'Corolla Cross', 'Camry', 'Avensis', 'Prius', 'C-HR', 'RAV4', 'Rush', 'Fortuner', 'Highlander', 'Land Cruiser', 'Land Cruiser Prado', '4Runner', 'Sequoia', 'Hilux', 'Tacoma', 'Tundra', 'Hiace', 'Coaster', 'Dyna', 'Noah', 'Avanza', 'Innova'],
            'Volkswagen' => ['Up', 'Polo', 'Golf', 'Jetta', 'Passat', 'Arteon', 'T-Cross', 'T-Roc', 'Tiguan', 'Touareg', 'Atlas', 'ID.3', 'ID.4', 'Caddy', 'Transporter', 'Crafter', 'Amarok'],
            'Volvo' => ['S60', 'S90', 'V60', 'V90', 'XC40', 'XC60', 'XC90', 'EX30'],
            'Wuling' => ['Hongguang', 'Almaz', 'Air EV'],
            'Autre' => [],
        ],
        'colors' => [
            'blanc' => ['Blanc', '#ffffff'], 'noir' => ['Noir', '#111827'], 'gris' => ['Gris', '#9ca3af'], 'argent' => ['Argent', '#d1d5db'],
            'bleu' => ['Bleu', '#1d4ed8'], 'rouge' => ['Rouge', '#dc2626'], 'vert' => ['Vert', '#15803d'], 'beige' => ['Beige', '#e7d8b8'],
            'marron' => ['Marron', '#78350f'], 'jaune' => ['Jaune', '#facc15'], 'orange' => ['Orange', '#f97316'], 'autre' => ['Autre', 'linear-gradient(135deg,#f472b6,#60a5fa)'],
        ],
        'conditions' => ['occasion' => 'Occasion', 'neuf' => 'Neuf', 'reconditionne' => 'Reconditionne'],
        // Acompte pour reserver un vehicule a vendre (deduit du prix chez le vendeur).
        // Annonces d'exemple (catalogue de demarrage) visibles sur le site.
        'show_demo' => (bool) env('VOITURE_SHOW_DEMO', true),
        'sale_deposit' => (int) env('VOITURE_SALE_DEPOSIT', 100000),
        // Simulation de mensualite (indicative) : apport, duree, taux annuel.
        'finance' => ['down_payment_percent' => 20, 'months' => 36, 'annual_rate' => 12],
        'fuels' => ['essence' => 'Essence', 'diesel' => 'Diesel', 'hybride' => 'Hybride', 'electrique' => 'Electrique', 'gpl' => 'GPL'],
        'categories' => [
            'citadine' => 'Citadine', 'berline' => 'Berline', 'suv' => 'SUV', '4x4' => '4x4', 'pickup' => 'Pick-up',
            'monospace' => 'Monospace', 'coupe' => 'Coupe / Cabriolet', 'utilitaire' => 'Utilitaire', 'luxe' => 'Luxe',
        ],
        'options' => [
            'Climatisation', 'GPS', 'Camera de recul', 'Radar de recul', 'Bluetooth', 'Toit ouvrant', 'Sieges cuir',
            'Jantes alliage', 'Regulateur de vitesse', 'Demarrage sans cle', '4 roues motrices', 'Vitres electriques',
        ],
        // Prestations proposees dans le champ de recherche Voiture > Mecanique.
        'mechanic_services' => [
            'Mecanique generale', 'Entretien / revision', 'Vidange', 'Diagnostic', 'Freinage', 'Pneumatiques',
            'Climatisation', 'Carrosserie', 'Peinture', 'Electricite auto', 'Batterie', 'Pieces detachees',
            'Depannage / remorquage', 'Poids lourds', 'Lavage', 'Gardiennage',
        ],
    ],

    // Divertissement devient "Tourisme" (slug technique inchange).
    'tourisme' => [
        'label' => 'Tourisme',
        'tabs' => [
            // "venues" : salles affichees avec les evenements (et a leur place s'il n'y en a pas).
            'concerts' => ['label' => 'Concerts & spectacles', 'source' => 'events', 'categories' => ['concert', 'spectacle'], 'venues' => ['salle_spectacle', 'cinema']],
            'cinema' => ['label' => 'Cinema', 'source' => 'events', 'categories' => ['cinema'], 'venues' => ['cinema']],
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
