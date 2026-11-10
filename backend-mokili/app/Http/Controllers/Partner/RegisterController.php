<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Devenir partenaire" : inscription en ligne des societes (bus, train,
 * canots rapides, taxis, compagnies aeriennes, agences de billets, garages,
 * hotels...). Le compte est cree tout de suite ; ses offres sont mises en
 * ligne apres validation par MOKILI TOUR (Admin > Agents & Partenaires).
 */
class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Partner/Register', [
            'activities' => User::PARTNER_ACTIVITIES,
            'activity' => array_key_exists((string) $request->query('activite'), User::PARTNER_ACTIVITIES) ? $request->query('activite') : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'partner_activity' => ['required', Rule::in(array_keys(User::PARTNER_ACTIVITIES))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'accept' => ['accepted'],
        ], [], ['company_name' => 'nom de la societe', 'partner_activity' => 'activite', 'accept' => 'conditions']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'partner',
            'company_name' => $data['company_name'],
            'partner_activity' => $data['partner_activity'],
            'partner_verified_at' => null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('partner.dashboard')->with('success', 'Bienvenue ! Votre espace est pret. Notre equipe va verifier votre societe pour mettre vos offres en ligne.');
    }
}
