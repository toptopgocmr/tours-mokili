<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Separate, non-public login entry point for partners, at
 * /partner/login - distinct from the client-facing /login.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Partner/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        if (Auth::user()->role !== 'partner') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => "Ce compte n'a pas acces a l'espace partenaire.",
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('partner.dashboard'));
    }
}
