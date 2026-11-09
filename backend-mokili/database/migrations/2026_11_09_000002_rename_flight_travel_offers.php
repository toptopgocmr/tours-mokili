<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les offres de type "vol" creees par le seeder s'appelaient "Sejour a ..." :
 * sous l'onglet Vols, elles ressemblaient a des sejours. On les renomme
 * "Vol Brazzaville - <destination>".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('travel_offers')
            ->where('type', 'vol')
            ->where('title', 'like', 'Sejour a %')
            ->orderBy('id')
            ->each(function ($offer) {
                DB::table('travel_offers')->where('id', $offer->id)->update([
                    'title' => 'Vol '.($offer->origin_city ?: 'Brazzaville').' - '.$offer->destination_city,
                ]);
            });
    }

    public function down(): void
    {
        // Pas de retour arriere : simple renommage d'affichage.
    }
};
