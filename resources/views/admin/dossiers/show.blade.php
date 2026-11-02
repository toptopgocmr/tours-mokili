@extends('admin.layouts.app')

@section('title', $dossier->numero_dossier)

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h1>{{ $dossier->numero_dossier }}</h1>
            <p class="muted">Client : {{ $dossier->client->nom ?? '—' }} · Agent : {{ $dossier->agent->name ?? '—' }} · <span class="badge">{{ $dossier->statut }}</span></p>
        </div>
        <div>
            <a href="{{ route('admin.devis.create', $dossier) }}" class="btn">+ Créer un devis</a>
            <a href="{{ route('admin.factures.create', $dossier) }}" class="btn orange">+ Créer une facture</a>
        </div>
    </div>

    <div class="card">
        <div class="section-title">1. Identification du projet</div>
        <div class="grid cols-4">
            <div><label>Date</label>{{ optional($dossier->date_intervention)->format('d/m/Y') }}</div>
            <div><label>Heure</label>{{ $dossier->heure_intervention }}</div>
            <div><label>Responsable technique</label>{{ $dossier->responsable_technique ?? '—' }}</div>
            <div><label>Téléphone client</label>{{ $dossier->client->telephone ?? '—' }}</div>
        </div>
        <div class="grid cols-2" style="margin-top:10px;">
            <div><label>Adresse</label>{{ $dossier->client->adresse ?? '—' }}</div>
        </div>

        <div class="section-title">2. Nature du site</div>
        <div class="grid cols-2">
            <div><label>Type de site</label>{{ $dossier->type_site ?? '—' }}</div>
            <div><label>Niveaux</label>{{ $dossier->niveaux ?? '—' }}</div>
        </div>

        <div class="section-title">3. Objectif du client</div>
        <p>{{ collect($dossier->objectifs)->implode(', ') ?: '—' }}</p>

        <div class="section-title">4. Réseau électrique</div>
        <div class="grid cols-4">
            <div><label>Type réseau</label>{{ $dossier->type_reseau ?? '—' }}</div>
            <div><label>Tension (V)</label>{{ $dossier->tension_v ?? '—' }}</div>
            <div><label>Fréquence (Hz)</label>{{ $dossier->frequence_hz ?? '—' }}</div>
            <div><label>Puissance souscrite (kVA)</label>{{ $dossier->puissance_souscrite_kva ?? '—' }}</div>
        </div>
        <div class="grid cols-2" style="margin-top:10px;">
            <div><label>Facture E²C</label>{{ $dossier->facture_e2c ? 'Oui' : 'Non' }}</div>
        </div>

        <div class="section-title">5. Groupe électrogène</div>
        <div class="grid cols-4">
            <div><label>Marque</label>{{ $dossier->groupe_marque ?? '—' }}</div>
            <div><label>Puissance (kVA)</label>{{ $dossier->groupe_puissance_kva ?? '—' }}</div>
            <div><label>Année</label>{{ $dossier->groupe_annee ?? '—' }}</div>
            <div><label>Problèmes</label>{{ collect($dossier->groupe_problemes)->implode(', ') ?: '—' }}</div>
        </div>

        <div class="section-title">6. Inventaire des charges</div>
        <table>
            <thead><tr><th>Équipement</th><th>Qté</th><th>Puissance (W)</th><th>Priorité</th></tr></thead>
            <tbody>
                @forelse ($dossier->chargeItems as $item)
                    <tr>
                        <td>{{ $item->equipement === 'autres' ? $item->equipement_autre : $item->equipement }}</td>
                        <td>{{ $item->quantite }}</td>
                        <td>{{ $item->puissance_w }}</td>
                        <td>{{ $item->priorite }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Aucun équipement renseigné.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">7. Mesures électriques</div>
        <div class="grid cols-4">
            <div><label>Tension P1/P2/P3</label>{{ $dossier->tension_p1 }} / {{ $dossier->tension_p2 }} / {{ $dossier->tension_p3 }} V</div>
            <div><label>Courant</label>{{ $dossier->courant_a ?? '—' }} A</div>
            <div><label>Fréquence</label>{{ $dossier->frequence_mesuree_hz ?? '—' }} Hz</div>
            <div><label>Facteur de puissance</label>{{ $dossier->facteur_puissance ?? '—' }}</div>
        </div>
        <div class="grid cols-3" style="margin-top:10px;">
            <div><label>P (kW)</label>{{ $dossier->puissance_active_kw ?? '—' }}</div>
            <div><label>S (kVA)</label>{{ $dossier->puissance_apparente_kva ?? '—' }}</div>
            <div><label>Q (kVAr)</label>{{ $dossier->puissance_reactive_kvar ?? '—' }}</div>
        </div>

        <div class="section-title">8. Analyse de la toiture</div>
        <div class="grid cols-4">
            <div><label>Type</label>{{ $dossier->type_toiture ?? '—' }}</div>
            <div><label>Orientation</label>{{ $dossier->orientation ?? '—' }}</div>
            <div><label>Inclinaison</label>{{ $dossier->inclinaison_deg ?? '—' }}°</div>
            <div><label>Surface</label>{{ $dossier->surface_m2 ?? '—' }} m²</div>
        </div>
        <div class="grid cols-2" style="margin-top:10px;">
            <div><label>Ombrage</label>{{ $dossier->ombrage ?? '—' }}</div>
        </div>

        <div class="section-title">9. Documents & Photos</div>
        <div class="grid cols-4">
            @forelse ($dossier->documents as $doc)
                <div>
                    <a href="{{ $doc->url }}" target="_blank">
                        <img src="{{ $doc->url }}" alt="{{ $doc->type }}" style="width:100%; border-radius:8px; border:1px solid #eee;">
                    </a>
                    <div class="muted" style="text-align:center;">{{ $doc->type }}</div>
                </div>
            @empty
                <p class="muted">Aucun document/photo joint.</p>
            @endforelse
        </div>

        <div class="section-title">10. Recommandation préliminaire</div>
        <div class="grid cols-4">
            <div><label>Type</label>{{ $dossier->recommandation_type ?? '—' }}</div>
            <div><label>Puissance PV (kWc)</label>{{ $dossier->puissance_pv_kwc ?? '—' }}</div>
            <div><label>Onduleur</label>{{ $dossier->onduleur ?? '—' }}</div>
            <div><label>Batteries</label>{{ $dossier->batteries ?? '—' }}</div>
        </div>

        <div class="section-title">11. Observations</div>
        <p>{{ $dossier->observations ?? '—' }}</p>
    </div>

    <div class="card">
        <h2>Devis liés</h2>
        <table>
            <thead><tr><th>N°</th><th>Date</th><th>Total TTC</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse ($dossier->devis as $d)
                    <tr>
                        <td>{{ $d->numero_devis }}</td>
                        <td>{{ optional($d->date_devis)->format('d/m/Y') }}</td>
                        <td>{{ number_format($d->total_ttc, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge">{{ $d->statut }}</span></td>
                        <td><a href="{{ route('admin.devis.show', $d) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Aucun devis pour ce dossier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Factures liées</h2>
        <table>
            <thead><tr><th>N°</th><th>Date</th><th>Total TTC</th><th>Payé</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse ($dossier->factures as $f)
                    <tr>
                        <td>{{ $f->numero_facture }}</td>
                        <td>{{ optional($f->date_facture)->format('d/m/Y') }}</td>
                        <td>{{ number_format($f->total_ttc, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($f->montant_paye, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge">{{ $f->statut }}</span></td>
                        <td><a href="{{ route('admin.factures.show', $f) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucune facture pour ce dossier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
