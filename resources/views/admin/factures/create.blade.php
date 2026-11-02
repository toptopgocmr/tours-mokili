@extends('admin.layouts.app')

@section('title', 'Nouvelle facture')

@section('content')
    <h1>Nouvelle facture</h1>
    <p class="muted">Dossier {{ $dossier->numero_dossier }} · Client : {{ $dossier->client->nom }}</p>

    <form method="POST" action="{{ route('admin.factures.store') }}" class="card">
        @csrf
        <input type="hidden" name="dossier_id" value="{{ $dossier->id }}">
        @if ($devis)
            <input type="hidden" name="devis_id" value="{{ $devis->id }}">
        @endif

        <div class="grid cols-3">
            <div>
                <label>Date de facture</label>
                <input type="date" name="date_facture" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label>Date d'échéance</label>
                <input type="date" name="date_echeance" value="{{ now()->addDays(15)->toDateString() }}">
            </div>
            <div>
                <label>Taux TVA (%)</label>
                <input type="number" step="0.01" name="taux_tva" value="{{ $tauxTvaDefaut }}" required>
            </div>
        </div>

        <div class="section-title">Lignes de la facture</div>
        <table id="lignes-table">
            <thead>
                <tr><th>Désignation</th><th>Description</th><th style="width:90px;">Qté</th><th style="width:140px;">Prix unitaire (FCFA)</th><th></th></tr>
            </thead>
            <tbody id="lignes-body"></tbody>
        </table>
        <button type="button" class="btn secondaire" id="ajouter-ligne" style="margin-top:10px;">+ Ajouter une ligne</button>

        <div class="section-title">Notes</div>
        <textarea name="notes" rows="3"></textarea>

        <div style="margin-top:20px;">
            <button type="submit" class="btn orange">Enregistrer la facture</button>
        </div>
    </form>

    <template id="ligne-template">
        <tr>
            <td><input type="text" name="lignes[__i__][designation]" value="__designation__" required></td>
            <td><input type="text" name="lignes[__i__][description]" value="__description__"></td>
            <td><input type="number" step="0.01" name="lignes[__i__][quantite]" value="__quantite__" required></td>
            <td><input type="number" step="0.01" name="lignes[__i__][prix_unitaire]" value="__prix__" required></td>
            <td><button type="button" class="btn secondaire supprimer-ligne">✕</button></td>
        </tr>
    </template>

    <script>
        let compteur = 0;
        const corps = document.getElementById('lignes-body');
        const modele = document.getElementById('ligne-template').innerHTML;

        function ajouterLigne(designation = '', description = '', quantite = 1, prix = 0) {
            let html = modele.replaceAll('__i__', compteur)
                .replace('__designation__', designation)
                .replace('__description__', description)
                .replace('__quantite__', quantite)
                .replace('__prix__', prix);
            const tmp = document.createElement('tbody');
            tmp.innerHTML = html;
            corps.appendChild(tmp.firstElementChild);
            compteur++;
        }

        document.getElementById('ajouter-ligne').addEventListener('click', () => ajouterLigne());
        corps.addEventListener('click', (e) => {
            if (e.target.classList.contains('supprimer-ligne')) {
                e.target.closest('tr').remove();
            }
        });

        @if ($devis)
            @foreach ($devis->lignes as $ligne)
                ajouterLigne(@json($ligne->designation), @json($ligne->description), {{ $ligne->quantite }}, {{ $ligne->prix_unitaire }});
            @endforeach
        @else
            ajouterLigne();
        @endif
    </script>
@endsection
