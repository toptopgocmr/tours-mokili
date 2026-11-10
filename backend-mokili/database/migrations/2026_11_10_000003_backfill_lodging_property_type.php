<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les anciens logements n'avaient pas de type (villa / hotel / appartement) :
 * les filtres "Villa", "Hotel", "Appartement" affichaient 0. On deduit le
 * type du titre ; le partenaire ou l'admin peut le corriger ensuite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lodging_listings', 'property_type')) {
            return;
        }

        DB::table('lodging_listings')->where(fn ($q) => $q->whereNull('property_type')->orWhere('property_type', ''))
            ->orderBy('id')->get(['id', 'title'])->each(function ($l) {
                $t = mb_strtolower($l->title);
                $type = match (true) {
                    str_contains($t, 'villa') || str_contains($t, 'maison') => 'villa',
                    str_contains($t, 'hotel') || str_contains($t, 'hôtel') || str_contains($t, 'suite') || str_contains($t, 'chambre') => 'hotel',
                    default => 'appartement',
                };
                DB::table('lodging_listings')->where('id', $l->id)->update(['property_type' => $type]);
            });
    }

    public function down(): void {}
};
