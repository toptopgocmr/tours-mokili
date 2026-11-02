@extends('admin.layouts.app')

@section('title', 'Fiches terrain')

@section('content')
    <h1>Fiches d'expertise technique</h1>
    <p class="muted">Fiches remontées par les agents terrain.</p>

    <div class="card">
        <form method="GET" class="grid cols-3" style="margin-bottom:16px;">
            <input type="text" name="q" placeholder="Rechercher (n° dossier, client)" value="{{ request('q') }}">
            <select name="statut">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon','soumis','valide','devis_envoye','facture_envoyee'] as $s)
                    <option value="{{ $s }}" @selected(request('statut') === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filtrer</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>N° Dossier</th>
                    <th>Client</th>
                    <th>Agent</th>
                    <th>Type de site</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dossiers as $dossier)
                    <tr>
                        <td>{{ $dossier->numero_dossier }}</td>
                        <td>{{ $dossier->client->nom ?? '—' }}</td>
                        <td>{{ $dossier->agent->name ?? '—' }}</td>
                        <td>{{ $dossier->type_site }}</td>
                        <td>{{ optional($dossier->date_intervention)->format('d/m/Y') }}</td>
                        <td><span class="badge">{{ $dossier->statut }}</span></td>
                        <td><a href="{{ route('admin.dossiers.show', $dossier) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Aucune fiche trouvée.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:16px;">{{ $dossiers->links() }}</div>
    </div>
@endsection
