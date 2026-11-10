<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MODE PRESENTATION : photo "apercu" d'une societe partenaire, visible
 * uniquement par l'admin / les agents MOKILI TOUR connectes (demarchage
 * commercial). Elle n'est publiee pour le public qu'apres accord de la
 * societe (bouton "Publier la photo" dans le back-office).
 */
return new class extends Migration
{
    // Photos officielles (site de l'hotel / communique de presse) ou de sa fiche TripAdvisor.
    private const HOTELS = [
        'Hilton Brazzaville Les Tours Jumelles' => 'https://stories-editor.hilton.com/emea/wp-content/uploads/sites/2/2024/12/Hilton-Brazzaville-Les-Tours-Jumelles-Hotel-Residences-Exterior.jpg?w=1224&h=861&crop=1&q=75',
        'Grand Lancaster Brazzaville' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/2c/9b/ec/dc/grand-lancaster-brazzaville.jpg?w=1400&h=800&s=1',
        "Radisson Blu M'Bamou Palace Hotel" => 'https://media.radissonhotels.net/image/radisson-blu-mbamou-palace-hotel-brazzaville/exterior/16256-113928-f62716698_3xl.jpg?impolicy=Card',
        'Pefaco Hotel Maya-Maya' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/11/c3/06/8b/espace-mbongui-compose.jpg?w=1200&h=-1&s=1',
        'Hotel Ledger Plaza Maya-Maya' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/16/1e/71/f1/ledger-plaza-maya-maya.jpg?w=1200&h=1200&s=1',
        'Olympic Palace' => 'https://olympic-palace-hotel.net/assets/img/home.jpg',
        'GHS Hotel' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/16/8f/4d/f1/deluxe-room.jpg?w=900&h=-1&s=1',
        "Mikhael's Hotel" => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/19/e3/7f/ed/mikhael-s-hotel-residence.jpg?w=1100&h=-1&s=1',
        'Hotel Le Paradis' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/04/30/4f/66/chambre-standard.jpg?w=1200&h=1200&s=1',
        'Hotel Africa' => 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/16/20/31/08/hotel-africa.jpg?w=1200&h=1200&s=1',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('businesses', 'preview_image')) {
            Schema::table('businesses', fn (Blueprint $t) => $t->string('preview_image', 500)->nullable());
        }

        foreach (self::HOTELS as $name => $url) {
            DB::table('businesses')->where('name', $name)->whereNull('preview_image')->update(['preview_image' => $url]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'preview_image')) {
            Schema::table('businesses', fn (Blueprint $t) => $t->dropColumn('preview_image'));
        }
    }
};
