<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Separate, non-public login entry point for staff (admin/agent), at
 * /admin/login - distinct from the client-facing /login. Not linked
 * from the main site nav; the URL is handed directly to staff.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Login');
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

        if (! in_array(Auth::user()->role, ['admin', 'agent'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => "Ce compte n'a pas acces a l'espace back-office.",
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
