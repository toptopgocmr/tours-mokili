<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ANNUAIRE : complements de coordonnees publiques verifiees
 * (Journal officiel du Congo, sites officiels des compagnies, presse).
 * Les champs deja remplis ne sont pas ecrases.
 */
return new class extends Migration
{
    private const DATA = [
        'CFAO Motors Congo - Brazzaville' => ['address' => 'Boulevard Denis Sassou Nguesso, M\'Pila, BP 247'],
        'CFAO Motors Congo - Pointe-Noire' => ['address' => '13, rue Cote Mateve, BP 1110'],
        'Afrijet Business Service' => ['website' => 'https://www.afrijet.com', 'description' => 'Compagnie gabonaise (FlyGabon) : vols Libreville - Brazzaville, Douala, Malabo... Agence commerciale a Brazzaville depuis juillet 2025.'],
        'Canadian Airways Congo' => ['address' => 'Aeroport international Maya-Maya'],
        'Gozem' => ['description' => 'Super-application de mobilite lancee a Brazzaville en novembre 2025 : taxis, courses, paiement Mobile Money.'],
    ];

    public function up(): void
    {
        foreach (self::DATA as $name => $values) {
            $b = DB::table('businesses')->where('name', $name)->first();
            if (! $b) {
                continue;
            }
            // On complete sans ecraser une valeur deja saisie (sauf la description).
            $set = collect($values)->filter(fn ($v, $k) => $k === 'description' || empty($b->{$k}))->all();
            if ($set) {
                DB::table('businesses')->where('id', $b->id)->update($set + ['updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
    }
};
