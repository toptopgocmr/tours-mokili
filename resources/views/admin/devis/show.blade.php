@extends('admin.layouts.app')

@section('title', $devis->numero_devis)

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h1>Devis {{ $devis->numero_devis }}</h1>
            <p class="muted">Dossier {{ $devis->dossier->numero_dossier }} · Client : {{ $devis->client->nom }} · <span class="badge">{{ $devis->statut }}</span></p>
        </div>
        <div>
            <a href="{{ route('admin.devis.pdf', $devis) }}" target="_blank" class="btn secondaire">Télécharger PDF</a>
            @if ($devis->statut === 'accepte' && ! $devis->facture)
                <a href="{{ route('admin.factures.from-devis', $devis) }}" class="btn orange">Transformer en facture</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="grid cols-3">
            <div><label>Date</label>{{ $devis->date_devis->format('d/m/Y') }}</div>
            <div><label>Validité</label>{{ $devis->validite_jours }} jours</div>
            <div><label>Créé par</label>{{ $devis->creePar->name ?? '—' }}</div>
        </div>

        <table style="margin-top:16px;">
            <thead><tr><th>Désignation</th><th>Qté</th><th>PU (FCFA)</th><th>Montant HT</th></tr></thead>
            <tbody>
                @foreach ($devis->lignes as $ligne)
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
            <div>Sous-total HT : <strong>{{ number_format($devis->sous_total, 0, ',', ' ') }} FCFA</strong></div>
            <div>TVA ({{ $devis->taux_tva }}%) : <strong>{{ number_format($devis->montant_tva, 0, ',', ' ') }} FCFA</strong></div>
            <div style="font-size:18px;">Total TTC : <strong>{{ number_format($devis->total_ttc, 0, ',', ' ') }} FCFA</strong></div>
        </div>

        @if ($devis->notes)
            <div class="section-title">Notes</div>
            <p>{{ $devis->notes }}</p>
        @endif
    </div>

    <div class="card">
        <h2>Changer le statut</h2>
        <form method="POST" action="{{ route('admin.devis.statut', $devis) }}" style="display:flex; gap:10px; align-items:end;">
            @csrf
            @method('PATCH')
            <div style="flex:1;">
                <select name="statut">
                    @foreach (['brouillon','envoye','accepte','refuse'] as $s)
                        <option value="{{ $s }}" @selected($devis->statut === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn">Mettre à jour</button>
        </form>
    </div>
@endsection
