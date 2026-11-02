@extends('admin.layouts.app')

@section('title', $facture->numero_facture)

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h1>Facture {{ $facture->numero_facture }}</h1>
            <p class="muted">Dossier {{ $facture->dossier->numero_dossier }} · Client : {{ $facture->client->nom }} · <span class="badge">{{ $facture->statut }}</span></p>
        </div>
        <div>
            <a href="{{ route('admin.factures.pdf', $facture) }}" target="_blank" class="btn secondaire">Télécharger PDF</a>
        </div>
    </div>

    <div class="card">
        <div class="grid cols-3">
            <div><label>Date</label>{{ $facture->date_facture->format('d/m/Y') }}</div>
            <div><label>Échéance</label>{{ optional($facture->date_echeance)->format('d/m/Y') ?? '—' }}</div>
            <div><label>Créée par</label>{{ $facture->creePar->name ?? '—' }}</div>
        </div>

        <table style="margin-top:16px;">
            <thead><tr><th>Désignation</th><th>Qté</th><th>PU (FCFA)</th><th>Montant HT</th></tr></thead>
            <tbody>
                @foreach ($facture->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->designation }}<br><span class="muted">{{ $ligne->description }}</span></td>
                        <td>{{ $ligne->quantite }}</td>
                        <td>{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                        <td>{{ number_format($ligne->montant_ht, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="text-align:right; margin-top:12px;">
            <div>Sous-total HT : <strong>{{ number_format($facture->sous_total, 0, ',', ' ') }} FCFA</strong></div>
            <div>TVA ({{ $facture->taux_tva }}%) : <strong>{{ number_format($facture->montant_tva, 0, ',', ' ') }} FCFA</strong></div>
            <div style="font-size:18px;">Total TTC : <strong>{{ number_format($facture->total_ttc, 0, ',', ' ') }} FCFA</strong></div>
            <div>Payé : <strong>{{ number_format($facture->montant_paye, 0, ',', ' ') }} FCFA</strong></div>
            <div>Solde restant : <strong>{{ number_format($facture->solde, 0, ',', ' ') }} FCFA</strong></div>
        </div>
    </div>

    <div class="card grid cols-2">
        <div>
            <h2>Changer le statut</h2>
            <form method="POST" action="{{ route('admin.factures.statut', $facture) }}" style="display:flex; gap:10px; align-items:end;">
                @csrf
                @method('PATCH')
                <div style="flex:1;">
                    <select name="statut">
                        @foreach (['brouillon','envoyee','payee_partiel','payee','annulee'] as $s)
                            <option value="{{ $s }}" @selected($facture->statut === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn">Mettre à jour</button>
            </form>
        </div>
        <div>
            <h2>Enregistrer un paiement</h2>
            <form method="POST" action="{{ route('admin.factures.paiement', $facture) }}" style="display:flex; gap:10px; align-items:end;">
                @csrf
                <div style="flex:1;">
                    <input type="number" step="0.01" name="montant" placeholder="Montant en FCFA" required>
                </div>
                <button type="submit" class="btn orange">Ajouter</button>
            </form>
        </div>
    </div>
@endsection
