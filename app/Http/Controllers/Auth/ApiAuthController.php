<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    /**
     * Connexion (app mobile - agents terrain). Retourne un token Sanctum.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ["Identifiants incorrects."],
            ]);
        }

        if (! $user->actif) {
            throw ValidationException::withMessages([
                'email' => ["Ce compte est désactivé."],
            ]);
        }

        $deviceName = $credentials['device_name'] ?? $request->userAgent() ?? 'mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion : révoque le token courant.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
