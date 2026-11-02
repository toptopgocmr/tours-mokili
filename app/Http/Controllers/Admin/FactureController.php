<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Devis;
use App\Models\Dossier;
use App\Models\Facture;
use App\Services\NumeroGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FactureController extends Controller
{
    public function create(Dossier $dossier)
    {
        $dossier->load('client');
        $tauxTvaDefaut = config('services.ssea.taux_tva', 18);

        return view('admin.factures.create', ['dossier' => $dossier, 'devis' => null, 'tauxTvaDefaut' => $tauxTvaDefaut]);
    }

    public function createFromDevis(Devis $devis)
    {
        $devis->load('dossier.client', 'lignes');

        return view('admin.factures.create', [
            'dossier' => $devis->dossier,
            'devis' => $devis,
            'tauxTvaDefaut' => $devis->taux_tva,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dossier_id' => ['required', 'exists:dossiers,id'],
            'devis_id' => ['nullable', 'exists:devis,id'],
            'date_facture' => ['required', 'date'],
            'date_echeance' => ['nullable', 'date'],
            'taux_tva' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.designation' => ['required', 'string', 'max:255'],
            'lignes.*.description' => ['nullable', 'string'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $dossier = Dossier::findOrFail($data['dossier_id']);

        $facture = DB::transaction(function () use ($data, $dossier, $request) {
            $facture = Facture::create([
                'numero_facture' => NumeroGeneratorService::facture(),
                'devis_id' => $data['devis_id'] ?? null,
                'dossier_id' => $dossier->id,
                'client_id' => $dossier->client_id,
                'created_by' => $request->user()->id,
                'date_facture' => $data['date_facture'],
                'date_echeance' => $data['date_echeance'] ?? null,
                'taux_tva' => $data['taux_tva'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lignes'] as $i => $ligne) {
                $facture->lignes()->create([
                    'designation' => $ligne['designation'],
                    'description' => $ligne['description'] ?? null,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'ordre' => $i,
                ]);
            }

            $dossier->update(['statut' => 'facture_envoyee']);

            return $facture;
        });

        return redirect()->route('admin.factures.show', $facture)->with('success', 'Facture créée avec succès.');
    }

    public function show(Facture $facture)
    {
        $facture->load('dossier.client', 'client', 'lignes', 'devisOrigine', 'creePar');

        return view('admin.factures.show', compact('facture'));
    }

    public function pdf(Facture $facture)
    {
        $facture->load('dossier.client', 'client', 'lignes');
        $entreprise = config('services.ssea');

        $pdf = Pdf::loadView('pdf.facture', compact('facture', 'entreprise'));

        return $pdf->stream("{$facture->numero_facture}.pdf");
    }

    public function updateStatut(Request $request, Facture $facture)
    {
        $data = $request->validate([
            'statut' => ['required', 'in:brouillon,envoyee,payee_partiel,payee,annulee'],
        ]);

        $facture->update($data);

        return back()->with('success', 'Statut de la facture mis à jour.');
    }

    public function enregistrerPaiement(Request $request, Facture $facture)
    {
        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
        ]);

        $facture->montant_paye += $data['montant'];
        $facture->statut = $facture->montant_paye >= $facture->total_ttc ? 'payee' : 'payee_partiel';
        $facture->save();

        return back()->with('success', 'Paiement enregistré.');
    }
}
