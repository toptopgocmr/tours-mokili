<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ANNUAIRE : coordonnees publiques verifiees (telephone, e-mail, adresse,
 * site web) des societes deja presentes. Sources : listes d'hotels de l'ONU
 * (indico.un.org, 2024) et de l'OACI, annuaires professionnels hoteliers,
 * sites officiels (Volvo Trucks Congo, Ethiopian Airlines, Campus France).
 * Seules les valeurs trouvees sont ecrites ; rien n'est invente.
 */
return new class extends Migration
{
    private const DATA = [
        'Radisson Blu M\'Bamou Palace Hotel' => [
            'phone' => '+242 05 050 60 60', 'email' => 'reservations.brazzaville@radissonblu.com',
            'address' => 'Avenue Amilcar Cabral, Corniche, Centre-ville',
            'website' => 'https://www.radissonhotels.com/fr-fr/hotels/radisson-blu-brazzaville-mbamou',
        ],
        'Hilton Brazzaville Les Tours Jumelles' => [
            'phone' => '+242 06 751 15 15', 'whatsapp' => null, 'email' => 'reservations@toursjumelles.com',
            'address' => 'Les Tours Jumelles, rue Moundzeli, Mpila',
            'website' => 'https://www.hilton.com/en/hotels/bzvjmhi-hilton-brazzaville-les-tours-jumelles-hotel-and-residences/',
        ],
        'Grand Lancaster Brazzaville' => [
            'phone' => '+242 05 666 96 96', 'address' => 'Avenue Auxence Ickonga, BP 1178, Centre-ville',
            'website' => 'https://grandlancasterbrazzaville.com',
            'description' => 'Hotel du centre-ville (ex Ledger Plaza Maya-Maya), proche de la cathedrale et du Memorial de Brazza.',
        ],
        'GHS Hotel' => [
            'phone' => '+242 06 512 12 12', 'email' => 'reservation@ghsafrica.com',
            'address' => 'Boulevard Denis Sassou Nguesso, rond-point La Coupole', 'website' => 'https://www.ghscongo.com',
        ],
        'Olympic Palace' => [
            'phone' => '+242 05 730 16 16', 'email' => 'reservation@olympic-palace-hotel.net',
            'address' => '15, avenue de l\'Amitie, BP 1050',
        ],
        'Mikhael\'s Hotel' => [
            'phone' => '+242 05 366 66 60', 'address' => '67, avenue Nelson Mandela', 'website' => 'https://www.mikhaelshotel.com',
        ],
        'Hotel Le Paradis' => [
            'phone' => '+242 06 611 61 01', 'address' => '10, avenue de l\'Amitie, Centre-ville',
        ],
        'Pefaco Hotel Maya-Maya' => [
            'address' => 'Boulevard Denis Sassou Nguesso, face a l\'aeroport Maya-Maya',
        ],
        'Ethiopian Airlines' => [
            'phone' => '+242 06 671 20 20', 'address' => 'Avenue Foch, face au ministere des Finances, Centre-ville',
            'website' => 'https://www.ethiopianairlines.com',
        ],
        'ASKY Airlines' => ['phone' => '+242 06 634 44 48', 'website' => 'https://www.flyasky.com'],
        'Air France' => ['address' => 'Avenue Amilcar Cabral, Centre-ville', 'website' => 'https://www.airfrance.fr'],
        'Royal Air Maroc' => [
            'address' => '10, avenue de l\'Amitie, Centre-ville', 'website' => 'https://www.royalairmaroc.com',
            'description' => 'Vols vers Casablanca et correspondances. Attention : ligne de Brazzaville suspendue par la compagnie depuis mai 2026, verifier la reprise avant de reserver.',
        ],
        'RwandAir' => ['website' => 'https://www.rwandair.com'],
        'TAAG Angola Airlines' => ['website' => 'https://www.taag.com'],
        'SMT Congo - Brazzaville' => ['email' => 'info.congo@smt.network', 'website' => 'https://www.smt.network'],
        'SMT Congo - Pointe-Noire' => [
            'email' => 'info.congo@smt.network', 'website' => 'https://www.smt.network',
            'address' => 'Route de l\'aeroport, entre l\'Hotel FLM et le ruisseau (1055)',
        ],
        'Institut Francais du Congo (IFC)' => ['address' => 'Espace Andre Malraux, place de la Republique, BP 2141'],
        'Institut francais du Congo - Pointe-Noire' => ['address' => 'Avenue Moe Kaat Matou'],
    ];

    public function up(): void
    {
        foreach (self::DATA as $name => $values) {
            $values = array_filter($values, fn ($v) => $v !== null);
            DB::table('businesses')->where('name', $name)->update($values + ['updated_at' => now()]);
        }

        // Doublon : "Hotel Ledger Plaza Maya-Maya" est l'ancien nom du Grand Lancaster.
        if (DB::table('businesses')->where('name', 'Grand Lancaster Brazzaville')->exists()) {
            DB::table('businesses')->where('name', 'Hotel Ledger Plaza Maya-Maya')->update(['is_active' => false, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Donnees de reference : pas de retour arriere.
    }
};
