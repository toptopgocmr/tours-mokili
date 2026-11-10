<?php

/*
|--------------------------------------------------------------------------
| Passerelles de paiement MOKILI TOUR - collecte (PAY-IN) et decaissement
| (PAY-OUT) Mobile Money.
|--------------------------------------------------------------------------
| 4 agregateurs, chacun active/desactive par variable d'environnement
| (cles API) ET depuis le back-office (/admin/passerelles - table
| gateway_settings). Les cles ne sont JAMAIS stockees en base ni envoyees
| au client : elles restent dans les variables Railway.
|
| Webhooks a declarer chez chaque agregateur :
|   https://<APP_URL>/api/webhooks/payments/{peex|pawapay|maishapay|digitwace}
*/

return [

    // Passerelle utilisee par defaut quand le client / l'admin n'en choisit pas.
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'peex'),

    // Passerelle preferee par pays (ISO2). Ecrasable depuis le back-office.
    'routing' => [
        'CG' => env('PAYMENT_ROUTE_CG', 'maishapay'),
        'CM' => env('PAYMENT_ROUTE_CM', 'digitwace'),
        'CD' => env('PAYMENT_ROUTE_CD', 'maishapay'),
        'GA' => env('PAYMENT_ROUTE_GA', 'pawapay'),
    ],

    // Ordre des passerelles de COLLECTE par pays : la premiere disponible
    // est proposee par defaut au client, les suivantes en alternative.
    // Congo-Brazzaville : MaishaPay, puis Digitwace, puis Peex (pawaPay en dernier).
    'priority' => [
        'CG' => ['maishapay', 'digitwace', 'peex', 'pawapay'],
    ],

    // Delai (secondes) au-dela duquel un paiement "en attente" sans
    // callback est re-verifie aupres de l'agregateur (polling app mobile).
    'pending_recheck_after' => (int) env('PAYMENT_PENDING_RECHECK', 20),

    'gateways' => [

        'peex' => [
            'label' => 'Peex',
            'collect' => true,
            'payout' => true,
            'countries' => ['CM', 'CG', 'GA', 'TD', 'CF', 'GQ'],
            // Le client Peex existant (config/services.php > peex) est reutilise.
            'payout_path' => env('PEEX_PAYOUT_PATH', 'clients/request_payment'),
            'webhook_secret' => env('PEEX_WEBHOOK_SECRET'),
        ],

        'pawapay' => [
            'label' => 'pawaPay',
            'collect' => true,
            'payout' => true,
            'countries' => ['CG', 'CM', 'GA', 'CD', 'CI', 'SN', 'BJ', 'BF'],
            // Afrique de l'Ouest : on debite en XOF (1 XAF = 1 XOF, parite fixe).
            'xof_countries' => ['CI', 'SN', 'BJ', 'BF'],
            'env' => env('PAWAPAY_ENV', 'sandbox'),
            'base_url' => env('PAWAPAY_ENV', 'sandbox') === 'production'
                ? env('PAWAPAY_PRODUCTION_URL', 'https://api.pawapay.io')
                : env('PAWAPAY_SANDBOX_URL', 'https://api.sandbox.pawapay.io'),
            'token' => env('PAWAPAY_API_TOKEN'),
            'timeout' => (int) env('PAWAPAY_TIMEOUT', 20),
            // Message affiche sur le telephone du client (4 a 22 caracteres).
            'customer_message' => env('PAWAPAY_CUSTOMER_MESSAGE', 'MOKILI TOUR'),
        ],

        'maishapay' => [
            'label' => 'MaishaPay',
            'collect' => true,
            'payout' => true,
            'countries' => ['CG', 'CD', 'CM'],
            // Web API v2.1 - https://docs.maishapay.net/front
            // 0 = sandbox (cles MP-SB...), 1 = production (cles MP-LIVE...).
            'mode' => (int) env('MAISHAPAY_GATEWAY_MODE', 0),
            'base_url' => env('MAISHAPAY_BASE_URL', 'https://marchand.maishapay.online/api'),
            'collect_path' => env('MAISHAPAY_COLLECT_PATH', '/collect/v2/store/mobileMoney'),
            'payout_path' => env('MAISHAPAY_PAYOUT_PATH', '/b2c/transfer'),
            'status_path' => env('MAISHAPAY_STATUS_PATH', '/transaction/rest/v2/check'),
            'public_key' => env('MAISHAPAY_PUBLIC_KEY'),
            'secret_key' => env('MAISHAPAY_SECRET_KEY'),
            // La doc indique parfois des montants en centimes : passer a 100 si
            // MaishaPay demande 100x le prix lors du paiement test.
            'amount_multiplier' => (float) env('MAISHAPAY_AMOUNT_MULTIPLIER', 1),
            'xaf_code' => env('MAISHAPAY_XAF_CODE', 'XAF'),
            'timeout' => (int) env('MAISHAPAY_TIMEOUT', 25),
            'webhook_secret' => env('MAISHAPAY_WEBHOOK_SECRET'),
        ],

        'digitwace' => [
            'label' => 'Digitwace',
            'collect' => true,
            'payout' => true,
            'countries' => ['CG', 'CM', 'GA', 'CI', 'SN'],
            // La documentation Digitwace est fournie au marchand : les
            // chemins sont donc parametrables sans toucher au code.
            'base_url' => env('DIGITWACE_BASE_URL'),
            'api_key' => env('DIGITWACE_API_KEY'),
            'api_secret' => env('DIGITWACE_API_SECRET'),
            'collect_path' => env('DIGITWACE_COLLECT_PATH', '/api/v1/payments/collect'),
            'payout_path' => env('DIGITWACE_PAYOUT_PATH', '/api/v1/payments/payout'),
            'status_path' => env('DIGITWACE_STATUS_PATH', '/api/v1/payments/{reference}'),
            'timeout' => (int) env('DIGITWACE_TIMEOUT', 25),
            'webhook_secret' => env('DIGITWACE_WEBHOOK_SECRET'),
        ],
    ],

    /*
    | Operateurs Mobile Money par pays, avec le code attendu par chaque
    | agregateur (pawaPay "provider", MaishaPay "provider"...).
    */
    'networks' => [
        'CG' => [
            'MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => 'MTN_MOMO_COG', 'maishapay' => 'MTN'],
            'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => 'AIRTEL_COG', 'maishapay' => 'AIRTEL'],
        ],
        'CM' => [
            'MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => 'MTN_MOMO_CMR', 'maishapay' => 'MTN'],
            'ORANGE' => ['label' => 'Orange Money', 'pawapay' => 'ORANGE_CMR', 'maishapay' => 'ORANGE'],
        ],
        'GA' => [
            'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => 'AIRTEL_GAB', 'maishapay' => 'AIRTEL'],
            'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null],
        ],
        'CD' => [
            'MPESA' => ['label' => 'M-Pesa (Vodacom)', 'pawapay' => 'VODACOM_MPESA_COD', 'maishapay' => 'MPESA'],
            'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => 'AIRTEL_COD', 'maishapay' => 'AIRTEL'],
            'ORANGE' => ['label' => 'Orange Money', 'pawapay' => 'ORANGE_COD', 'maishapay' => 'ORANGE'],
            'AFRICELL' => ['label' => 'Africell Money', 'pawapay' => null, 'maishapay' => 'AFRICEL'],
        ],
        'TD' => [
            'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null],
            'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null],
        ],
        // Zone franc CFA de l'Ouest (XOF, meme parite que le XAF).
        'CI' => [
            'ORANGE' => ['label' => 'Orange Money', 'pawapay' => 'ORANGE_CIV', 'maishapay' => null],
            'MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => 'MTN_MOMO_CIV', 'maishapay' => null],
            'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null],
            'WAVE' => ['label' => 'Wave', 'pawapay' => null, 'maishapay' => null],
        ],
        'SN' => [
            'ORANGE' => ['label' => 'Orange Money', 'pawapay' => 'ORANGE_SEN', 'maishapay' => null],
            'FREE' => ['label' => 'Free Money', 'pawapay' => 'FREE_SEN', 'maishapay' => null],
            'WAVE' => ['label' => 'Wave', 'pawapay' => null, 'maishapay' => null],
        ],
        'BJ' => [
            'MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => 'MTN_MOMO_BEN', 'maishapay' => null],
            'MOOV' => ['label' => 'Moov Money', 'pawapay' => 'MOOV_BEN', 'maishapay' => null],
        ],
        'BF' => [
            'ORANGE' => ['label' => 'Orange Money', 'pawapay' => 'ORANGE_BFA', 'maishapay' => null],
            'MOOV' => ['label' => 'Moov Money', 'pawapay' => 'MOOV_BFA', 'maishapay' => null],
        ],
        'ML' => ['ORANGE' => ['label' => 'Orange Money', 'pawapay' => null, 'maishapay' => null], 'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null]],
        'NE' => ['AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null], 'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null]],
        'TG' => ['MOOV' => ['label' => 'Moov Money (Flooz)', 'pawapay' => null, 'maishapay' => null], 'TMONEY' => ['label' => 'T-Money', 'pawapay' => null, 'maishapay' => null]],
        'GN' => ['ORANGE' => ['label' => 'Orange Money', 'pawapay' => null, 'maishapay' => null], 'MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => null, 'maishapay' => null]],
        'CF' => ['ORANGE' => ['label' => 'Orange Money', 'pawapay' => null, 'maishapay' => null], 'MOOV' => ['label' => 'Moov Money', 'pawapay' => null, 'maishapay' => null]],
        'GQ' => ['GETESA' => ['label' => 'Muni Dinero', 'pawapay' => null, 'maishapay' => null]],
        'GH' => ['MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => null, 'maishapay' => null], 'VODAFONE' => ['label' => 'Telecel Cash', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'AirtelTigo Money', 'pawapay' => null, 'maishapay' => null]],
        'NG' => ['MTN' => ['label' => 'MTN MoMo PSB', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel SmartCash', 'pawapay' => null, 'maishapay' => null]],
        'KE' => ['MPESA' => ['label' => 'M-Pesa (Safaricom)', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null]],
        'UG' => ['MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null]],
        'TZ' => ['MPESA' => ['label' => 'M-Pesa (Vodacom)', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null], 'TIGO' => ['label' => 'Mixx by Yas', 'pawapay' => null, 'maishapay' => null]],
        'RW' => ['MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null]],
        'ZM' => ['MTN' => ['label' => 'MTN Mobile Money', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null]],
        'MZ' => ['MPESA' => ['label' => 'M-Pesa (Vodacom)', 'pawapay' => null, 'maishapay' => null], 'EMOLA' => ['label' => 'e-Mola', 'pawapay' => null, 'maishapay' => null]],
        'MW' => ['AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null], 'TNM' => ['label' => 'TNM Mpamba', 'pawapay' => null, 'maishapay' => null]],
        'MG' => ['MVOLA' => ['label' => 'MVola', 'pawapay' => null, 'maishapay' => null], 'ORANGE' => ['label' => 'Orange Money', 'pawapay' => null, 'maishapay' => null], 'AIRTEL' => ['label' => 'Airtel Money', 'pawapay' => null, 'maishapay' => null]],
    ],

    // Indicatif telephonique par pays (normalisation MSISDN).
    'dial_codes' => [
        'CG' => '242', 'CM' => '237', 'GA' => '241', 'CD' => '243', 'TD' => '235', 'CF' => '236', 'GQ' => '240',
        'CI' => '225', 'SN' => '221', 'BJ' => '229', 'BF' => '226', 'ML' => '223', 'NE' => '227', 'TG' => '228',
        'GN' => '224', 'GW' => '245', 'SL' => '232', 'LR' => '231', 'GH' => '233', 'NG' => '234', 'GM' => '220',
        'MR' => '222', 'KE' => '254', 'UG' => '256', 'TZ' => '255', 'RW' => '250', 'BI' => '257', 'ET' => '251',
        'ZM' => '260', 'MW' => '265', 'MZ' => '258', 'ZW' => '263', 'AO' => '244', 'MG' => '261', 'KM' => '269',
        'DJ' => '253', 'SO' => '252', 'SS' => '211', 'ZA' => '27', 'BW' => '267', 'NA' => '264', 'LS' => '266',
        'SZ' => '268', 'MA' => '212', 'TN' => '216', 'DZ' => '213', 'EG' => '20', 'ST' => '239', 'CV' => '238',
        'FR' => '33', 'BE' => '32', 'CH' => '41', 'CA' => '1', 'US' => '1', 'GB' => '44', 'CN' => '86', 'AE' => '971',
        'TR' => '90', 'IN' => '91',
    ],

    // Tous les pays proposes sur l'ecran de paiement Mobile Money. Ceux sans
    // passerelle active s'affichent "bientot disponible".
    'country_names' => [
        'CG' => 'Congo-Brazzaville', 'CD' => 'RD Congo', 'CM' => 'Cameroun', 'GA' => 'Gabon', 'TD' => 'Tchad',
        'CF' => 'Centrafrique', 'GQ' => 'Guinee Equatoriale', 'ST' => 'Sao Tome-et-Principe', 'AO' => 'Angola',
        'CI' => "Cote d'Ivoire", 'SN' => 'Senegal', 'BJ' => 'Benin', 'BF' => 'Burkina Faso', 'ML' => 'Mali',
        'NE' => 'Niger', 'TG' => 'Togo', 'GN' => 'Guinee', 'GW' => 'Guinee-Bissau', 'SL' => 'Sierra Leone',
        'LR' => 'Liberia', 'GH' => 'Ghana', 'NG' => 'Nigeria', 'GM' => 'Gambie', 'MR' => 'Mauritanie', 'CV' => 'Cap-Vert',
        'KE' => 'Kenya', 'UG' => 'Ouganda', 'TZ' => 'Tanzanie', 'RW' => 'Rwanda', 'BI' => 'Burundi', 'ET' => 'Ethiopie',
        'DJ' => 'Djibouti', 'SO' => 'Somalie', 'SS' => 'Soudan du Sud', 'ZM' => 'Zambie', 'MW' => 'Malawi',
        'MZ' => 'Mozambique', 'ZW' => 'Zimbabwe', 'MG' => 'Madagascar', 'KM' => 'Comores', 'ZA' => 'Afrique du Sud',
        'BW' => 'Botswana', 'NA' => 'Namibie', 'LS' => 'Lesotho', 'SZ' => 'Eswatini', 'MA' => 'Maroc', 'TN' => 'Tunisie',
        'DZ' => 'Algerie', 'EG' => 'Egypte', 'FR' => 'France', 'BE' => 'Belgique', 'CH' => 'Suisse', 'GB' => 'Royaume-Uni',
        'CA' => 'Canada', 'US' => 'Etats-Unis', 'CN' => 'Chine', 'AE' => 'Emirats arabes unis', 'TR' => 'Turquie', 'IN' => 'Inde',
    ],
];
