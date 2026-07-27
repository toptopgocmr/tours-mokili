<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Minimal OAuth2 "authorization code" client for Google, Facebook and
 * Instagram sign-in, built on Guzzle (already a dependency) instead of
 * Laravel Socialite.
 *
 * Why not Socialite: this app is deployed on Railway via an automatic
 * Railpack build that runs `composer install` (not `update`) - adding a
 * package here requires regenerating composer.lock, which needs a local
 * PHP/Composer toolchain. Hand-editing composer.json without a matching
 * lock file breaks that build (composer refuses to install on a
 * content-hash mismatch) - the exact failure mode already fixed once in
 * 2026_01_02_000001_create_freight_offers_table. Guzzle avoids that risk
 * entirely.
 *
 * See SocialAuthController for how this is used, and config/services.php
 * for where each provider's client_id/secret come from.
 */
class SocialOAuthService
{
    /** @var array<string, array<string, string>> */
    private const PROVIDERS = [
        'google' => [
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'profile_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'scope' => 'openid email profile',
        ],
        'facebook' => [
            'authorize_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'profile_url' => 'https://graph.facebook.com/v19.0/me',
            'scope' => 'email public_profile',
        ],
        // "Instagram API with Instagram Login" (successor to the
        // deprecated Instagram Basic Display API). Requires an
        // Instagram professional (business/creator) account on the
        // signing-in user's side - Meta does not currently offer a
        // general-purpose "Login with Instagram" for personal accounts.
        // Verify against https://developers.facebook.com/docs/instagram-platform
        // at setup time in case this has moved again.
        'instagram' => [
            'authorize_url' => 'https://www.instagram.com/oauth/authorize',
            'token_url' => 'https://api.instagram.com/oauth/access_token',
            'profile_url' => 'https://graph.instagram.com/me',
            'scope' => 'instagram_business_basic',
        ],
    ];

    public function __construct(private readonly Client $http = new Client())
    {
    }

    public static function supports(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDERS);
    }

    public function authorizationUrl(string $provider, string $redirectUri, string $state): string
    {
        $config = $this->config($provider);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::PROVIDERS[$provider]['scope'],
            'state' => $state,
        ];

        return self::PROVIDERS[$provider]['authorize_url'].'?'.http_build_query($params);
    }

    /**
     * Exchanges the authorization code for an access token, then fetches
     * the user's profile. Returns a normalized array:
     * ['id' => string, 'email' => ?string, 'name' => ?string, 'avatar' => ?string].
     *
     * @throws RuntimeException on any provider/network error.
     */
    public function fetchProfile(string $provider, string $code, string $redirectUri): array
    {
        $endpoints = self::PROVIDERS[$provider];
        $config = $this->config($provider);

        try {
            $accessToken = $this->exchangeCodeForToken($provider, $endpoints, $config, $code, $redirectUri);

            return $this->fetchUserProfile($provider, $endpoints, $accessToken);
        } catch (GuzzleException $e) {
            throw new RuntimeException("Echec de l'authentification $provider : ".$e->getMessage(), previous: $e);
        }
    }

    private function exchangeCodeForToken(string $provider, array $endpoints, array $config, string $code, string $redirectUri): string
    {
        if ($provider === 'facebook') {
            // Facebook's token endpoint takes plain query params on a GET.
            $response = $this->http->get($endpoints['token_url'], [
                'query' => [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);
        } else {
            // Google + Instagram both accept a standard form-encoded POST.
            $response = $this->http->post($endpoints['token_url'], [
                'form_params' => [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                ],
                'headers' => ['Accept' => 'application/json'],
            ]);
        }

        $body = json_decode((string) $response->getBody(), true);
        $token = $body['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException("Reponse $provider invalide : jeton d'acces manquant.");
        }

        return $token;
    }

    private function fetchUserProfile(string $provider, array $endpoints, string $accessToken): array
    {
        $query = match ($provider) {
            'google' => ['access_token' => $accessToken],
            'facebook' => ['access_token' => $accessToken, 'fields' => 'id,name,email,picture'],
            'instagram' => ['access_token' => $accessToken, 'fields' => 'id,username'],
        };

        $response = $this->http->get($endpoints['profile_url'], ['query' => $query]);
        $profile = json_decode((string) $response->getBody(), true) ?? [];

        return match ($provider) {
            'google' => [
                'id' => (string) ($profile['sub'] ?? ''),
                'email' => $profile['email'] ?? null,
                'name' => $profile['name'] ?? null,
                'avatar' => $profile['picture'] ?? null,
            ],
            'facebook' => [
                'id' => (string) ($profile['id'] ?? ''),
                'email' => $profile['email'] ?? null,
                'name' => $profile['name'] ?? null,
                'avatar' => $profile['picture']['data']['url'] ?? null,
            ],
            'instagram' => [
                'id' => (string) ($profile['id'] ?? ''),
                // Instagram's basic profile doesn't expose an email at all.
                'email' => null,
                'name' => $profile['username'] ?? null,
                'avatar' => null,
            ],
        };
    }

    private function config(string $provider): array
    {
        $config = config("services.$provider");

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new RuntimeException(
                "La connexion $provider n'est pas configuree cote serveur (client_id/client_secret manquants dans .env)."
            );
        }

        return $config;
    }
}
