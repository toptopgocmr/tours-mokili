@extends('admin.layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <h1>Tableau de bord</h1>
    <p class="muted">Vue d'ensemble de l'activité terrain, devis et facturation.</p>

    <div class="card grid cols-4">
        <div class="stat">
            <div class="num">{{ $stats['dossiers_total'] }}</div>
            <div class="label">Fiches terrain</div>
        </div>
        <div class="stat">
            <div class="num">{{ $stats['dossiers_soumis'] }}</div>
            <div class="label">Fiches à traiter</div>
        </div>
        <div class="stat">
            <div class="num">{{ $stats['devis_en_attente'] }}</div>
            <div class="label">Devis en attente</div>
        </div>
        <div class="stat">
            <div class="num">{{ $stats['factures_impayees'] }}</div>
            <div class="label">Factures impayées</div>
        </div>
    </div>

    <div class="card">
        <h2>Dernières fiches d'expertise</h2>
        <table>
            <thead>
                <tr>
                    <th>N° Dossier</th>
                    <th>Client</th>
                    <th>Agent</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($derniersDossiers as $dossier)
                    <tr>
                        <td>{{ $dossier->numero_dossier }}</td>
                        <td>{{ $dossier->client->nom ?? '—' }}</td>
                        <td>{{ $dossier->agent->name ?? '—' }}</td>
                        <td>{{ optional($dossier->date_intervention)->format('d/m/Y') }}</td>
                        <td><span class="badge">{{ $dossier->statut }}</span></td>
                        <td><a href="{{ route('admin.dossiers.show', $dossier) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucune fiche pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
