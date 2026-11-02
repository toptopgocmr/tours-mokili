@extends('admin.layouts.app')

@section('title', 'Nouveau devis')

@section('content')
    <h1>Nouveau devis</h1>
    <p class="muted">Dossier {{ $dossier->numero_dossier }} · Client : {{ $dossier->client->nom }}</p>

    <form method="POST" action="{{ route('admin.devis.store', $dossier) }}" class="card">
        @csrf

        <div class="grid cols-3">
            <div>
                <label>Date du devis</label>
                <input type="date" name="date_devis" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label>Validité (jours)</label>
                <input type="number" name="validite_jours" value="30" min="1" required>
            </div>
            <div>
                <label>Taux TVA (%)</label>
                <input type="number" step="0.01" name="taux_tva" value="{{ $tauxTvaDefaut }}" required>
            </div>
        </div>

        <div class="section-title">Lignes du devis</div>
        <table id="lignes-table">
            <thead>
                <tr><th>Désignation</th><th>Description</th><th style="width:90px;">Qté</th><th style="width:140px;">Prix unitaire (FCFA)</th><th></th></tr>
            </thead>
            <tbody id="lignes-body"></tbody>
        </table>
        <button type="button" class="btn secondaire" id="ajouter-ligne" style="margin-top:10px;">+ Ajouter une ligne</button>

        <div class="section-title">Notes</div>
        <textarea name="notes" rows="3" placeholder="Conditions particulières, délai de livraison, etc."></textarea>

        <div style="margin-top:20px;">
            <button type="submit" class="btn">Enregistrer le devis</button>
        </div>
    </form>

    <template id="ligne-template">
        <tr>
            <td><input type="text" name="lignes[__i__][designation]" required></td>
            <td><input type="text" name="lignes[__i__][description]"></td>
            <td><input type="number" step="0.01" name="lignes[__i__][quantite]" value="1" required></td>
            <td><input type="number" step="0.01" name="lignes[__i__][prix_unitaire]" value="0" required></td>
            <td><button type="button" class="btn secondaire supprimer-ligne">✕</button></td>
        </tr>
    </template>

    <script>
        let compteur = 0;
        const corps = document.getElementById('lignes-body');
        const modele = document.getElementById('ligne-template').innerHTML;

        function ajouterLigne() {
            const html = modele.replaceAll('__i__', compteur);
            const tmp = document.createElement('tbody');
            tmp.innerHTML = html;
            corps.appendChild(tmp.firstElementChild);
            compteur++;
        }

        document.getElementById('ajouter-ligne').addEventListener('click', ajouterLigne);
        corps.addEventListener('click', (e) => {
            if (e.target.classList.contains('supprimer-ligne')) {
                e.target.closest('tr').remove();
            }
        });

        ajouterLigne();
    </script>
@endsection
