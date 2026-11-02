<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Devis;
use App\Models\Dossier;
use App\Services\NumeroGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevisController extends Controller
{
    public function create(Dossier $dossier)
    {
        $dossier->load('client', 'chargeItems');
        $tauxTvaDefaut = config('services.ssea.taux_tva', 18);

        return view('admin.devis.create', compact('dossier', 'tauxTvaDefaut'));
    }

    public function store(Request $request, Dossier $dossier)
    {
        $data = $request->validate([
            'date_devis' => ['required', 'date'],
            'validite_jours' => ['required', 'integer', 'min:1'],
            'taux_tva' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.designation' => ['required', 'string', 'max:255'],
            'lignes.*.description' => ['nullable', 'string'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $devis = DB::transaction(function () use ($data, $dossier, $request) {
            $devis = Devis::create([
                'numero_devis' => NumeroGeneratorService::devis(),
                'dossier_id' => $dossier->id,
                'client_id' => $dossier->client_id,
                'created_by' => $request->user()->id,
                'date_devis' => $data['date_devis'],
                'validite_jours' => $data['validite_jours'],
                'taux_tva' => $data['taux_tva'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lignes'] as $i => $ligne) {
                $devis->lignes()->create([
                    'designation' => $ligne['designation'],
                    'description' => $ligne['description'] ?? null,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'ordre' => $i,
                ]);
            }

            $dossier->update(['statut' => 'devis_envoye']);

            return $devis;
        });

        return redirect()->route('admin.devis.show', $devis)->with('success', 'Devis créé avec succès.');
    }

    public function show(Devis $devis)
    {
        $devis->load('dossier.client', 'client', 'lignes', 'facture', 'creePar');

        return view('admin.devis.show', compact('devis'));
    }

    public function pdf(Devis $devis)
    {
        $devis->load('dossier.client', 'client', 'lignes');
        $entreprise = config('services.ssea');

        $pdf = Pdf::loadView('pdf.devis', compact('devis', 'entreprise'));

        return $pdf->stream("{$devis->numero_devis}.pdf");
    }

    public function updateStatut(Request $request, Devis $devis)
    {
        $data = $request->validate([
            'statut' => ['required', 'in:brouillon,envoye,accepte,refuse'],
        ]);

        $devis->update($data);

        return back()->with('success', 'Statut du devis mis à jour.');
    }
}
