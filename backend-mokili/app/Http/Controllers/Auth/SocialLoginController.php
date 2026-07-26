<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SocialOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Continuer avec Google / Facebook / Instagram" for the public WEB
 * site - session-based login (Auth::login + redirect to the homepage).
 *
 * This is the web counterpart of Api\SocialAuthController, which issues
 * a Sanctum token and redirects to the Flutter app's custom URL scheme
 * instead of logging into a browser session. Both share the same
 * SocialOAuthService and the same find-or-create-user rules, so a
 * client who signs up on the web and later opens the app (or vice
 * versa) with the same provider account lands on the same User record.
 */
class SocialLoginController extends Controller
{
    public function __construct(private readonly SocialOAuthService $oauth)
    {
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        try {
            $state = Crypt::encryptString(json_encode(['provider' => $provider, 'ts' => time()]));
            $url = $this->oauth->authorizationUrl($provider, $this->callbackUrl($provider), $state);

            return redirect()->away($url);
        } catch (RuntimeException $e) {
            return redirect()->route('login')->withErrors(['social' => $e->getMessage()]);
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
            Auth::login($user, remember: true);

            return redirect()->intended(route('home'));
        } catch (RuntimeException $e) {
            return redirect()->route('login')->withErrors(['social' => $e->getMessage()]);
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
        return url("/auth/{$provider}/callback");
    }
}
