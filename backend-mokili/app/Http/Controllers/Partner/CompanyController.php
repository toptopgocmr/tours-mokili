<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Espace partenaire - MON ENTREPRISE : nom de la societe, activite, contact. */
class CompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        $u = $request->user();

        return Inertia::render('Partner/Company', [
            'company' => $u->only(['name', 'email', 'phone', 'company_name', 'partner_activity']) + ['verified' => $u->isVerifiedPartner()],
            'activities' => User::PARTNER_ACTIVITIES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'partner_activity' => ['required', Rule::in(array_keys(User::PARTNER_ACTIVITIES))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($u->id)],
        ]);
        $u->update($data);

        return back()->with('success', 'Informations de l\'entreprise enregistrees.');
    }
}
