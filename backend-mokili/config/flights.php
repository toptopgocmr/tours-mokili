<?php

/*
|--------------------------------------------------------------------------
| Billetterie aerienne MOKILI TOUR
|--------------------------------------------------------------------------
| Moteur central de billetterie (cahier des charges "Billetterie aerienne").
| Chaque fournisseur (consolidateur, GDS, agregateur NDC) est un "driver"
| qui implemente App\Services\Flights\Contracts\FlightSupplier. Pour en
| ajouter un : creer la classe, l'ajouter dans "drivers", puis inserer une
| ligne dans la table flight_suppliers (driver = cle ci-dessous). Les cles
| API restent dans les variables d'environnement Railway.
*/

return [

    'drivers' => [
        'manual' => App\Services\Flights\Suppliers\ManualFareSupplier::class,
        'sandbox' => App\Services\Flights\Suppliers\SandboxSupplier::class,
        'galileo' => App\Services\Flights\Suppliers\GalileoSupplier::class,
        'duffel' => App\Services\Flights\Suppliers\DuffelSupplier::class,
        'travelpayouts' => App\Services\Flights\Suppliers\TravelpayoutsSupplier::class,
    ],

    // TRAVELPAYOUTS / AVIASALES (gratuit, inscription ouverte au Congo) : vrais
    // prix des vols constates recemment (indicatifs). travelpayouts.com >
    // rejoindre le programme Aviasales > Profil > API token.
    'travelpayouts' => [
        'token' => env('TRAVELPAYOUTS_TOKEN'),
        'marker' => env('TRAVELPAYOUTS_MARKER'),
        'endpoint' => env('TRAVELPAYOUTS_ENDPOINT', 'https://api.travelpayouts.com'),
        'max_offers' => (int) env('TRAVELPAYOUTS_MAX_OFFERS', 30),
        'timeout' => (int) env('TRAVELPAYOUTS_TIMEOUT', 20),
        'eur_xaf' => 655.957,
    ],

    // AIRLABS (plan gratuit) : programme des vols en direct (departs /
    // arrivees d'un aeroport, retards). Cle : airlabs.co > Sign up > API key.
    'airlabs' => [
        'key' => env('AIRLABS_API_KEY'),
        'endpoint' => env('AIRLABS_ENDPOINT', 'https://airlabs.co/api/v9'),
        'cache_minutes' => (int) env('AIRLABS_CACHE_MINUTES', 30),
        'timeout' => (int) env('AIRLABS_TIMEOUT', 15),
    ],

    // DUFFEL : vols en direct, inscription gratuite sur duffel.com (recherche
    // gratuite, frais seulement par reservation). Jeton : tableau de bord
    // Duffel > Developers > Access tokens. "duffel_test_..." = donnees de test
    // (compagnie fictive "Duffel Airways"), "duffel_live_..." = vrais vols.
    'duffel' => [
        'token' => env('DUFFEL_ACCESS_TOKEN'),
        'endpoint' => env('DUFFEL_ENDPOINT', 'https://api.duffel.com'),
        // true : apres le paiement Mobile Money, la commande est creee et payee
        // automatiquement sur le solde Duffel (billet emis). false : l'equipe
        // reserve dans le tableau de bord Duffel.
        'auto_ticketing' => (bool) env('DUFFEL_AUTO_TICKETING', false),
        'max_offers' => (int) env('DUFFEL_MAX_OFFERS', 50),
        'supplier_timeout_ms' => (int) env('DUFFEL_SUPPLIER_TIMEOUT_MS', 20000),
        'timeout' => (int) env('DUFFEL_TIMEOUT', 40),
        // Conversion vers le XAF (prix affiches et payes en XAF).
        'fx' => [
            'XAF' => 1,
            'EUR' => 655.957, // parite fixe du franc CFA
            'USD' => env('FX_USD_XAF'),
            'GBP' => env('FX_GBP_XAF'),
        ],
    ],

    // Travelport GALILEO (1G) via Universal API. Identifiants fournis par
    // Travelport a l'agence (pas le mot de passe du terminal) : a mettre dans
    // les variables Railway. Tant qu'ils sont vides, Galileo est ignore.
    //   Pre-production : https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/AirService
    //   Production     : https://emea.universal-api.travelport.com/B2BGateway/connect/uAPI/AirService
    'galileo' => [
        'endpoint' => env('GALILEO_ENDPOINT', 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/AirService'),
        'username' => env('GALILEO_USERNAME'),      // ex. Universal API/uAPI1234567890-abcd1234
        'password' => env('GALILEO_PASSWORD'),
        'target_branch' => env('GALILEO_TARGET_BRANCH'), // ex. P7012345
        'pcc' => env('GALILEO_PCC'),
        'air_schema' => env('GALILEO_AIR_SCHEMA', 'air_v52_0'),
        'currency' => env('GALILEO_CURRENCY', 'XAF'),
        'max_solutions' => (int) env('GALILEO_MAX_SOLUTIONS', 50),
        'timeout' => (int) env('GALILEO_TIMEOUT', 45),
    ],

    // Fournisseur de test (PNR / billets fictifs). Jamais en production.
    'sandbox_enabled' => (bool) env('FLIGHTS_SANDBOX', false),

    // Duree de validite d'une offre avant revalidation obligatoire du prix.
    'offer_ttl_minutes' => (int) env('FLIGHTS_OFFER_TTL', 30),

    // Marge MOKILI TOUR ajoutee au prix fournisseur (%), et frais de service
    // fixes par passager (hors bebe), affiches separement au client.
    'markup_percent' => (float) env('FLIGHTS_MARKUP_PERCENT', 0),
    'service_fee_per_passenger' => (float) env('FLIGHTS_SERVICE_FEE', 0),

    // Commission reversee a une agence partenaire (% de la marge).
    'agency_commission_percent' => (float) env('FLIGHTS_AGENCY_COMMISSION', 0),

    'max_passengers' => 9,

    // Recherche : nombre max d'offres conservees par recherche.
    'max_offers' => 60,

    // Dossiers bloques en *_PENDING depuis plus de N minutes : verification
    // automatique aupres du fournisseur (commande flights:reconcile).
    'reconcile_after_minutes' => (int) env('FLIGHTS_RECONCILE_AFTER', 10),

    // Alertes equipe billetterie (paiement confirme mais reservation /
    // emission en echec, demande apres-vente, etc.).
    'ops_email' => env('FLIGHTS_OPS_EMAIL'),

    'cabins' => [
        'economy' => 'Economique',
        'premium' => 'Premium economique',
        'business' => 'Affaires',
        'first' => 'Premiere',
    ],
];
