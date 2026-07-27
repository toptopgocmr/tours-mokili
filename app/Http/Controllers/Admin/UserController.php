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
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'role' => ['required', Rule::in(['agent', 'partner', 'admin'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([...$data, 'password' => Hash::make($data['password'])]);

        return redirect()->route('admin.users.index')->with('success', 'Compte cree avec succes.');
    }
}
