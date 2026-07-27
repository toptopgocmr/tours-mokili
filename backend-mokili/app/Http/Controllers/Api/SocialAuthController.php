<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SocialOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Continuer avec Google / Facebook / Instagram" for the Flutter app.
 *
 * Flow: the app opens GET /api/auth/{provider}/redirect in a system
 * browser tab (flutter_web_auth_2), the user approves access on the
 * provider's site, the provider redirects back here to
 * /api/auth/{provider}/callback, and this controller exchanges the code,
 * finds-or-creates the local User, and hands the app a Sanctum token by
 * redirecting to the app's own custom URL scheme
 * (mokilitour://auth-callback?token=...), which flutter_web_auth_2
 * intercepts and closes the browser tab automatically.
 *
 * No server-side session is used (the app is a stateless API client) -
 * CSRF protection on the redirect/callback round-trip comes from the
 * signed, timestamped `state` param instead of a session-stored value.
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialOAuthService $oauth)
    {
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        try {
            $state = Crypt::encryptString(json_encode(['provider' => $provider, 'ts' => time()]));
            $url = $this->oauth->authorizationUrl($provider, $this->callbackUrl($provider), $state);

            return redirect()->away($url);
        } catch (RuntimeException $e) {
            // Most likely: this provider's client_id/secret aren't set in
            // .env yet. Send the app a readable error instead of a raw 500.
            return redirect()->away($this->appDeepLink(['error' => $e->getMessage()]));
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        try {
            if ($request->filled('error')) {
                throw new RuntimeException($request->string('error_description')->toString() ?: 'Acces refuse.');
            }

            $this->assertValidState($request->string('state')->toString(), $provider);

            $code = $request->string('code')->toString();
            if ($code === '') {
                throw new RuntimeException('Code d\'autorisation manquant.');
            }

            $profile = $this->oauth->fetchProfile($provider, $code, $this->callbackUrl($provider));

            if ($profile['id'] === '') {
                throw new RuntimeException('Profil renvoye par le fournisseur invalide.');
            }

            $user = $this->findOrCreateUser($provider, $profile);
            $token = $user->createToken('mokili-mobile')->plainTextToken;

            return redirect()->away($this->appDeepLink(['token' => $token]));
        } catch (RuntimeException $e) {
            return redirect()->away($this->appDeepLink(['error' => $e->getMessage()]));
        }
    }

    private function findOrCreateUser(string $provider, array $profile): User
    {
        $user = User::where('provider', $provider)->where('provider_id', $profile['id'])->first();
        if ($user) {
            return $user;
        }

        // Same person previously registered with email/password (or
        // another provider) using the same email - link this provider to
        // that existing account instead of creating a duplicate.
        if ($profile['email']) {
            $existing = User::where('email', $profile['email'])->first();
            if ($existing) {
                $existing->fill(['provider' => $provider, 'provider_id' => $profile['id']]);
                if (! $existing->avatar && $profile['avatar']) {
                    $existing->avatar = $profile['avatar'];
                }
                $existing->save();

                return $existing;
            }
        }

        return User::create([
            'name' => $profile['name'] ?: ucfirst($provider).' User',
            // Instagram doesn't expose an email; synthesize a unique,
            // unambiguous placeholder so the NOT NULL/unique DB
            // constraint on users.email is satisfied. Not a real mailbox.
            'email' => $profile['email'] ?: "{$provider}_{$profile['id']}@social.mokili.local",
            'password' => Hash::make(Str::random(40)),
            'avatar' => $profile['avatar'],
            'provider' => $provider,
            'provider_id' => $profile['id'],
        ]);
    }

    private function assertSupported(string $provider): void
    {
        if (! SocialOAuthService::supports($provider)) {
            throw new NotFoundHttpException("Fournisseur inconnu : $provider");
        }
    }

    private function assertValidState(string $state, string $provider): void
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true);
        } catch (\Throwable) {
            throw new RuntimeException('Requete invalide (state).');
        }

        if (($payload['provider'] ?? null) !== $provider) {
            throw new RuntimeException('Requete invalide (provider mismatch).');
        }

        if (! isset($payload['ts']) || time() - (int) $payload['ts'] > 600) {
            throw new RuntimeException('Requete expiree, merci de reessayer.');
        }
    }

    private function callbackUrl(string $provider): string
    {
        return url("/api/auth/{$provider}/callback");
    }

    private function appDeepLink(array $params): string
    {
        $scheme = config('services.mobile_app.scheme', 'mokilitour');

        return "{$scheme}://auth-callback?".http_build_query($params);
    }
}
