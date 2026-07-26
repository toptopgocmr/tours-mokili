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

    /*
    |--------------------------------------------------------------------------
    | Social sign-in (Google / Facebook / Instagram) - see
    | app/Services/Auth/SocialOAuthService.php + SocialAuthController.
    | Implemented with raw Guzzle calls (already a dependency) instead of
    | Laravel Socialite, since this environment can't run `composer
    | require` / regenerate composer.lock safely.
    |
    | Each provider needs its app registered with the platform, with the
    | redirect URI below whitelisted there:
    |   {APP_URL}/api/auth/{provider}/callback
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    ],

    // Meta's Instagram login flow has changed more than once (Instagram
    // Basic Display is deprecated in favor of "Instagram API with
    // Instagram Login"). Double-check the current endpoints in Meta's
    // developer docs when configuring this - SocialOAuthService centralizes
    // them so only one place needs updating if they change again.
    'instagram' => [
        'client_id' => env('INSTAGRAM_CLIENT_ID'),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
    ],

    // Custom URL scheme the Flutter app registers (android/ios) to catch
    // the OAuth redirect - see lib/features/auth/services/auth_repository.dart.
    'mobile_app' => [
        'scheme' => env('MOBILE_APP_SCHEME', 'mokilitour'),
    ],

    // Swappable SMS sender for phone/OTP sign-in - see
    // app/Services/Sms/SmsGateway.php. Defaults to 'log' (writes the code
    // to the Laravel log instead of sending a real SMS) so phone login is
    // testable before a real provider (e.g. Twilio, Africa's Talking,
    // Orange SMS API) is wired up in production.
    'sms' => [
        'gateway' => env('SMS_GATEWAY', 'log'),
    ],

];
