@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $d = fn ($v, $f = 'd/m/Y') => $v ? \Carbon\Carbon::parse($v)->locale('fr')->translatedFormat($f) : '';
    $meta = $booking->meta ?? [];
    $paidAt = $payment?->paid_at ?? $booking->updated_at;
    $receiptNo = 'RC-'.$paidAt->format('Ymd').'-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
    $unitLabel = match ($kind) {
        'LodgingListing' => 'nuit(s)',
        'Vehicle' => 'jour(s)',
        'TravelOffer' => 'place(s)',
        default => 'unite(s)',
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recu {{ $receiptNo }} - MOKILI TOUR</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f6; font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #0b1b33; }
        .bar { max-width: 800px; margin: 24px auto 0; display: flex; justify-content: space-between; gap: 8px; padding: 0 16px; }
        .btn { border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #0b2a5b; color: #fff; }
        .btn-light { background: #fff; color: #0b2a5b; border: 1px solid #cfd6e3; }
        .sheet { max-width: 800px; margin: 16px auto 40px; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(11, 42, 91, .08); overflow: hidden; }
        .head { display: flex; justify-content: space-between; align-items: center; padding: 28px 36px; background: #0b2a5b; color: #fff; }
        .head img { height: 54px; }
        .head h1 { margin: 0; font-size: 22px; letter-spacing: .08em; }
        .head p { margin: 4px 0 0; font-size: 13px; color: #c9d4e8; text-align: right; }
        .body { padding: 28px 36px; }
        .stamp { display: inline-block; border: 3px solid #15803d; color: #15803d; font-weight: 800; letter-spacing: .15em; padding: 6px 14px; border-radius: 8px; transform: rotate(-4deg); font-size: 18px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 22px; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #6b7a90; font-weight: 700; margin: 0 0 6px; }
        .val { margin: 0; font-size: 14px; line-height: 1.55; }
        table { width: 100%; border-collapse: collapse; margin-top: 26px; font-size: 14px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #6b7a90; border-bottom: 2px solid #e3e8f0; padding: 10px 8px; }
        td { border-bottom: 1px solid #eef1f6; padding: 12px 8px; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        .total { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; background: #f5f7fb; border-radius: 12px; padding: 16px 20px; }
        .total strong { font-size: 26px; color: #b88a00; }
        .foot { padding: 18px 36px 28px; font-size: 12px; color: #6b7a90; border-top: 1px dashed #d6dce6; line-height: 1.6; }
        .muted { color: #6b7a90; font-size: 12px; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } .head, .body, .foot { padding-left: 20px; padding-right: 20px; } }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: none; }
            .head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .total { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('checkout.show', $booking) }}" class="btn btn-light">&larr; Retour</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer / Telecharger en PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('images/logo-transparent.png') }}" alt="MOKILI TOUR">
            <div>
                <h1>RECU DE PAIEMENT</h1>
                <p>N° {{ $receiptNo }}<br>Emis le {{ $d($paidAt, 'd F Y \a H:i') }}</p>
            </div>
        </div>

        <div class="body">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap">
                <div>
                    <p class="label">Reference de reservation</p>
                    <p class="val" style="font-size:18px; font-weight:700; font-family: Consolas, monospace">{{ $booking->reference }}</p>
                </div>
                <span class="stamp">PAYE</span>
            </div>

            <div class="grid">
                <div>
                    <p class="label">Client</p>
                    <p class="val"><strong>{{ $client->name }}</strong><br>{{ $client->email }}@if ($client->phone)<br>{{ $client->phone }}@endif</p>
                </div>
                <div>
                    <p class="label">Paiement</p>
                    <p class="val">
                        {{ $network }}@if ($phone) · +{{ $phone }}@endif<br>
                        Le {{ $d($paidAt, 'd/m/Y \a H:i') }}<br>
                        @if ($payment?->gateway_reference)<span class="muted">Transaction : {{ $payment->gateway_reference }}</span>@endif
                    </p>
                </div>
            </div>

            <table>
                <thead>
                    <tr><th>Prestation</th><th class="r">Quantite</th><th class="r">Prix unitaire</th><th class="r">Montant</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $title }}</strong>@if ($place)<br><span class="muted">{{ $place }}</span>@endif
                            @if ($booking->starts_at)
                                <br><span class="muted">
                                    @if ($kind === 'LodgingListing')Du {{ $d($booking->starts_at, 'd/m/Y') }} au {{ $d($booking->ends_at, 'd/m/Y') }}
                                    @elseif ($kind === 'Vehicle')Prise en charge le {{ $d($booking->starts_at, 'd/m/Y \a H:i') }}, restitution le {{ $d($booking->ends_at, 'd/m/Y') }}
                                    @else Le {{ $d($booking->starts_at, 'd/m/Y \a H:i') }}
                                    @endif
                                </span>
                            @endif
                            @if (! empty($meta['pickup_label']))<br><span class="muted">{{ $meta['pickup_label'] }}{{ ! empty($meta['pickup_address']) ? ' : '.$meta['pickup_address'] : '' }}</span>@endif
                            @if (! empty($meta['guests']))<br><span class="muted">{{ $meta['guests'] }} voyageur(s)</span>@endif
                        </td>
                        <td class="r">{{ $booking->quantity }} {{ $unitLabel }}</td>
                        <td class="r">{{ $money($booking->unit_price) }} {{ $booking->currency }}</td>
                        <td class="r"><strong>{{ $money($booking->total_amount) }} {{ $booking->currency }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="total">
                <span style="font-weight:600">Total paye</span>
                <strong>{{ $money($payment?->amount ?? $booking->total_amount) }} {{ $payment?->currency ?? $booking->currency }}</strong>
            </div>
        </div>

        <div class="foot">
            Ce recu atteste le paiement de la reservation ci-dessus sur MOKILI TOUR. Presentez-le (imprime ou sur votre telephone)
            avec votre piece d'identite lors de votre arrivee ou de la prise en charge.<br>
            MOKILI TOUR - Une filiale de Bilenium Conciergerie - Brazzaville, Republique du Congo - {{ parse_url(config('app.url'), PHP_URL_HOST) }}
        </div>
    </div>
</body>
</html>
