<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Peex Platform API (Verify Wallet / Remittance / Collect / Disbursement)
    | Docs: https://peex-api-docs.peexit.com
    |--------------------------------------------------------------------------
    */
    'peex' => [
        'env' => env('PEEX_ENV', 'sandbox'),
        'base_url' => env('PEEX_ENV', 'sandbox') === 'production'
            ? env('PEEX_PRODUCTION_BASE_URL', 'https://server.peexit.com/api/v1/')
            : env('PEEX_SANDBOX_BASE_URL', 'https://sandbox.peexit.com/api/v1/'),
        'secret_key' => env('PEEX_ENV', 'sandbox') === 'production'
            ? env('PEEX_PRODUCTION_KEY')
            : env('PEEX_SANDBOX_KEY'),
        'timeout' => (int) env('PEEX_TIMEOUT', 15),
    ],

];
