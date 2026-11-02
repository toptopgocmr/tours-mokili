<?php

namespace App\Services;

use App\Models\Devis;
use App\Models\Dossier;
use App\Models\Facture;

class NumeroGeneratorService
{
    public static function dossier(): string
    {
        $annee = now()->format('Y');
        $dernier = Dossier::where('numero_dossier', 'like', "SSEA-{$annee}-%")->orderByDesc('id')->first();
        $sequence = $dernier ? ((int) substr($dernier->numero_dossier, -4)) + 1 : 1;

        return sprintf('SSEA-%s-%04d', $annee, $sequence);
    }

    public static function devis(): string
    {
        $annee = now()->format('Y');
        $dernier = Devis::where('numero_devis', 'like', "DEV-{$annee}-%")->orderByDesc('id')->first();
        $sequence = $dernier ? ((int) substr($dernier->numero_devis, -4)) + 1 : 1;

        return sprintf('DEV-%s-%04d', $annee, $sequence);
    }

    public static function facture(): string
    {
        $annee = now()->format('Y');
        $dernier = Facture::where('numero_facture', 'like', "FAC-{$annee}-%")->orderByDesc('id')->first();
        $sequence = $dernier ? ((int) substr($dernier->numero_facture, -4)) + 1 : 1;

        return sprintf('FAC-%s-%04d', $annee, $sequence);
    }
}
