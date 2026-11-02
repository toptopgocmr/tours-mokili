<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use Illuminate\Http\Request;

class DossierController extends Controller
{
    public function index(Request $request)
    {
        $query = Dossier::with(['client', 'agent'])->withCount(['chargeItems', 'documents'])->latest('date_intervention');

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('numero_dossier', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$q}%"));
            });
        }

        $dossiers = $query->paginate(20)->withQueryString();

        return view('admin.dossiers.index', compact('dossiers'));
    }

    public function show(Dossier $dossier)
    {
        $dossier->load(['client', 'agent', 'chargeItems', 'documents', 'devis', 'factures']);

        return view('admin.dossiers.show', compact('dossier'));
    }
}
