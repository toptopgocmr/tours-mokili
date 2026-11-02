<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .entete { display: table; width: 100%; margin-bottom: 20px; }
        .entete .gauche { display: table-cell; width: 60%; }
        .entete .droite { display: table-cell; width: 40%; text-align: right; }
        h1 { color: #1e7d32; font-size: 20px; margin: 0; }
        .titre-orange { color: #f39c12; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f4f6f5; font-size: 11px; text-transform: uppercase; }
        .totaux { width: 260px; margin-left: auto; margin-top: 14px; }
        .totaux td { border: none; padding: 3px 8px; }
        .totaux .final { font-size: 15px; font-weight: bold; color: #1e7d32; }
        .footer { margin-top: 40px; font-size: 10px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="gauche">
            <h1>SMART SOLAR <span class="titre-orange">ENERGY AFRICA</span></h1>
            <div>{{ $entreprise['entreprise_adresse'] }}</div>
            <div>{{ $entreprise['entreprise_tel'] }} · {{ $entreprise['entreprise_email'] }}</div>
        </div>
        <div class="droite">
            <h2>FACTURE N° {{ $facture->numero_facture }}</h2>
            <div>Date : {{ $facture->date_facture->format('d/m/Y') }}</div>
            @if ($facture->date_echeance)<div>Échéance : {{ $facture->date_echeance->format('d/m/Y') }}</div>@endif
        </div>
    </div>

    <div>
        <strong>Client :</strong> {{ $facture->client->nom }}<br>
        @if ($facture->client->adresse)Adresse : {{ $facture->client->adresse }}<br>@endif
        @if ($facture->client->telephone)Téléphone : {{ $facture->client->telephone }}<br>@endif
        <strong>Dossier :</strong> {{ $facture->dossier->numero_dossier }}
        @if ($facture->devisOrigine)<br><strong>Devis d'origine :</strong> {{ $facture->devisOrigine->numero_devis }}@endif
    </div>

    <table>
        <thead>
            <tr><th>Désignation</th><th>Qté</th><th>PU ({{ $entreprise['devise'] }})</th><th>Montant HT</th></tr>
        </thead>
        <tbody>
            @foreach ($facture->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->designation }}@if($ligne->description)<br><small>{{ $ligne->description }}</small>@endif</td>
                    <td>{{ $ligne->quantite }}</td>
                    <td>{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                    <td>{{ number_format($ligne->montant_ht, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr><td>Sous-total HT</td><td>{{ number_format($facture->sous_total, 0, ',', ' ') }} {{ $entreprise['devise'] }}</td></tr>
        <tr><td>TVA ({{ $facture->taux_tva }}%)</td><td>{{ number_format($facture->montant_tva, 0, ',', ' ') }} {{ $entreprise['devise'] }}</td></tr>
        <tr class="final"><td>Total TTC</td><td>{{ number_format($facture->total_ttc, 0, ',', ' ') }} {{ $entreprise['devise'] }}</td></tr>
        <tr><td>Montant payé</td><td>{{ number_format($facture->montant_paye, 0, ',', ' ') }} {{ $entreprise['devise'] }}</td></tr>
        <tr><td>Solde dû</td><td>{{ number_format($facture->solde, 0, ',', ' ') }} {{ $entreprise['devise'] }}</td></tr>
    </table>

    @if ($facture->notes)
        <p><strong>Notes :</strong> {{ $facture->notes }}</p>
    @endif

    <div class="footer">{{ $entreprise['entreprise_nom'] }} — Merci de votre confiance.</div>
</body>
</html>
