<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only (role:admin) management of staff (agent) and partner
 * accounts. Regular clients register themselves via /register; this
 * screen is how MOKILI TOUR staff provisions the other two roles.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->when($request->string('role')->isNotEmpty(), fn ($q) => $q->where('role', $request->string('role')))
                ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(
                    'name', 'like', '%'.$request->string('search').'%'
                ))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only(['role', 'search']),
            'activities' => User::PARTNER_ACTIVITIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', ['activities' => User::PARTNER_ACTIVITIES]);
    }

    /** Valide (ou suspend) un partenaire inscrit depuis "Devenir partenaire". */
    public function verifyPartner(User $user): RedirectResponse
    {
        abort_unless($user->role === 'partner', 404);
        $verify = $user->partner_verified_at === null;
        $user->forceFill(['partner_verified_at' => $verify ? now() : null])->save();
        // A la validation, ses trajets en attente deviennent visibles ; a la suspension, ils sont masques.
        $user->travelOffers()->update(['is_active' => $verify]);

        return back()->with('success', $verify ? 'Partenaire valide : ses offres sont en ligne.' : 'Partenaire suspendu : ses offres sont masquees.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'role' => ['required', Rule::in(['agent', 'partner', 'admin'])],
            'password' => ['required', 'string', 'min:8'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'partner_activity' => ['nullable', Rule::in(array_keys(User::PARTNER_ACTIVITIES))],
        ]);

        // Un partenaire cree par l'administration est valide d'office.
        User::create([...$data, 'password' => Hash::make($data['password']),
            'partner_verified_at' => $data['role'] === 'partner' ? now() : null]);

        return redirect()->route('admin.users.index')->with('success', 'Compte cree avec succes.');
    }
}
