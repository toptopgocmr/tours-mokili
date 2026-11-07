<?php

/*
|--------------------------------------------------------------------------
| Billetterie aerienne MOKILI TOUR
|--------------------------------------------------------------------------
| Les connecteurs fournisseurs sont interchangeables : ajouter un driver =
| ajouter une classe qui implemente App\Services\Flights\Suppliers\FlightSupplierDriver
| et l'enregistrer ci-dessous, puis creer une ligne dans flight_suppliers.
| Les cles API restent dans les variables d'environnement (jamais en base).
*/

return [

    'drivers' => [
        'fake' => App\Services\Flights\Suppliers\FakeFlightSupplier::class,
        'amadeus' => App\Services\Flights\Suppliers\AmadeusFlightSupplier::class,
    ],

    // Marge Mokili ajoutee au cout fournisseur (pourcentage).
    'margin_percent' => (float) env('FLIGHTS_MARGIN_PERCENT', 8),

    // Duree de vie d'une offre avant revalidation obligatoire (minutes).
    'offer_ttl_minutes' => (int) env('FLIGHTS_OFFER_TTL', 20),

    // Nombre maximal de tentatives automatiques de reservation / emission.
    'max_attempts' => (int) env('FLIGHTS_MAX_ATTEMPTS', 3),

    // Plafond de transaction pour la mise en production controlee (0 = aucun).
    'max_order_amount' => (float) env('FLIGHTS_MAX_ORDER_AMOUNT', 0),

    // Scenarios de test du fournisseur simule (sandbox) - jamais en production.
    'fake' => [
        'fail_booking' => (bool) env('FLIGHTS_FAKE_FAIL_BOOKING', false),
        'fail_ticketing' => (bool) env('FLIGHTS_FAKE_FAIL_TICKETING', false),
        // Le billet est emis cote fournisseur MAIS la reponse expire (timeout).
        'timeout_after_ticketing' => (bool) env('FLIGHTS_FAKE_TIMEOUT_AFTER_TICKETING', false),
        // Variation de prix a la revalidation (en %, ex: 5 = +5 %).
        'price_change_percent' => (float) env('FLIGHTS_FAKE_PRICE_CHANGE', 0),
    ],

    // Amadeus Self-Service (https://developers.amadeus.com). Test : test.api.amadeus.com ; production : api.amadeus.com.
    'amadeus' => [
        'client_id' => env('AMADEUS_CLIENT_ID'),
        'client_secret' => env('AMADEUS_CLIENT_SECRET'),
        'base_url' => env('AMADEUS_BASE_URL', env('AMADEUS_ENV', 'test') === 'production' ? 'https://api.amadeus.com' : 'https://test.api.amadeus.com'),
        'currency' => env('AMADEUS_CURRENCY', 'XAF'),
        'max_offers' => (int) env('AMADEUS_MAX_OFFERS', 20),
        'timeout' => (int) env('AMADEUS_TIMEOUT', 25),
        // DELAY_TO_CANCEL (PNR garde N jours puis annule sans emission) ou CONFIRM.
        'ticketing_option' => env('AMADEUS_TICKETING_OPTION', 'DELAY_TO_CANCEL'),
        'ticketing_delay' => env('AMADEUS_TICKETING_DELAY', '6D'),
        // Indicatif telephonique par defaut (Congo-Brazzaville) si le numero n'a pas de +.
        'default_calling_code' => env('AMADEUS_DEFAULT_CALLING_CODE', '242'),
    ],

    // Suivi de vol : fournisseurs interroges dans l'ordre jusqu'a obtenir une reponse.
    'tracking' => [
        'providers' => array_filter(explode(',', env('FLIGHTS_TRACKING_PROVIDERS', 'amadeus,aviationstack'))),
        'window_hours' => (int) env('FLIGHTS_TRACKING_WINDOW_HOURS', 72),
        'delay_alert_minutes' => (int) env('FLIGHTS_DELAY_ALERT_MINUTES', 30),
        'aviationstack' => [
            'key' => env('AVIATIONSTACK_KEY'),
            'base_url' => env('AVIATIONSTACK_BASE_URL', 'https://api.aviationstack.com/v1'),
        ],
    ],
];
