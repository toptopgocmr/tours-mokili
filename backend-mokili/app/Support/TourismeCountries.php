<?php

namespace App\Support;

/**
 * TOURISME : liste des pays proposes sur la page "Choisissez un pays"
 * (avant la page des sorties). Le Congo-Brazzaville ouvre le catalogue local
 * MOKILI TOUR (/divertissement) ; les autres pays ouvrent leurs villes, avec
 * les sites touristiques tires de Wikipedia (voir Services\Tourisme\WorldPlaces).
 *
 * Pour ajouter un pays : une ligne ici (code ISO 2 lettres pour le drapeau).
 */
class TourismeCountries
{
    public const LOCAL = 'congo';

    public const CONTINENTS = ['Afrique', 'Europe', 'Ameriques', 'Asie & Moyen-Orient'];

    public const COUNTRIES = [
        // slug => [nom, code ISO, continent, villes (la 1re = ville par defaut)]
        'congo' => ['Congo-Brazzaville', 'cg', 'Afrique', ['Brazzaville', 'Pointe-Noire', 'Dolisie', 'Ouesso']],
        'rdc' => ['RD Congo', 'cd', 'Afrique', ['Kinshasa', 'Lubumbashi', 'Goma', 'Matadi']],
        'cameroun' => ['Cameroun', 'cm', 'Afrique', ['Douala', 'Yaounde', 'Kribi', 'Limbe']],
        'gabon' => ['Gabon', 'ga', 'Afrique', ['Libreville', 'Port-Gentil', 'Franceville']],
        'angola' => ['Angola', 'ao', 'Afrique', ['Luanda', 'Benguela', 'Lubango']],
        'cote-divoire' => ["Cote d'Ivoire", 'ci', 'Afrique', ['Abidjan', 'Yamoussoukro', 'Grand-Bassam', 'Assinie-Mafia']],
        'senegal' => ['Senegal', 'sn', 'Afrique', ['Dakar', 'Saly', 'Mbour']],
        'benin' => ['Benin', 'bj', 'Afrique', ['Cotonou', 'Porto-Novo', 'Ouidah']],
        'togo' => ['Togo', 'tg', 'Afrique', ['Lome', 'Kpalime']],
        'maroc' => ['Maroc', 'ma', 'Afrique', ['Marrakech', 'Casablanca', 'Fes', 'Rabat', 'Tanger', 'Agadir']],
        'tunisie' => ['Tunisie', 'tn', 'Afrique', ['Tunis', 'Djerba', 'Sousse', 'Hammamet']],
        'egypte' => ['Egypte', 'eg', 'Afrique', ['Le Caire', 'Louxor', 'Assouan', 'Alexandrie', 'Charm el-Cheikh']],
        'afrique-du-sud' => ['Afrique du Sud', 'za', 'Afrique', ['Le Cap', 'Johannesburg', 'Durban', 'Pretoria']],
        'tanzanie' => ['Tanzanie', 'tz', 'Afrique', ['Zanzibar', 'Dar es Salaam', 'Arusha']],
        'kenya' => ['Kenya', 'ke', 'Afrique', ['Nairobi', 'Mombasa']],
        'rwanda' => ['Rwanda', 'rw', 'Afrique', ['Kigali', 'Gisenyi']],
        'ethiopie' => ['Ethiopie', 'et', 'Afrique', ['Addis-Abeba', 'Lalibela', 'Gondar']],
        'france' => ['France', 'fr', 'Europe', ['Paris', 'Marseille', 'Lyon', 'Nice', 'Bordeaux']],
        'belgique' => ['Belgique', 'be', 'Europe', ['Bruxelles', 'Bruges', 'Anvers', 'Liege']],
        'royaume-uni' => ['Royaume-Uni', 'gb', 'Europe', ['Londres', 'Edimbourg', 'Manchester']],
        'espagne' => ['Espagne', 'es', 'Europe', ['Barcelone', 'Madrid', 'Seville', 'Malaga']],
        'portugal' => ['Portugal', 'pt', 'Europe', ['Lisbonne', 'Porto', 'Faro']],
        'italie' => ['Italie', 'it', 'Europe', ['Rome', 'Venise', 'Florence', 'Milan', 'Naples']],
        'allemagne' => ['Allemagne', 'de', 'Europe', ['Berlin', 'Munich', 'Francfort-sur-le-Main', 'Hambourg']],
        'pays-bas' => ['Pays-Bas', 'nl', 'Europe', ['Amsterdam', 'Rotterdam', 'La Haye']],
        'suisse' => ['Suisse', 'ch', 'Europe', ['Geneve', 'Zurich', 'Lausanne']],
        'turquie' => ['Turquie', 'tr', 'Europe', ['Istanbul', 'Antalya', 'Cappadoce']],
        'etats-unis' => ['Etats-Unis', 'us', 'Ameriques', ['New York', 'Miami', 'Los Angeles', 'Chicago', 'Las Vegas']],
        'canada' => ['Canada', 'ca', 'Ameriques', ['Montreal', 'Toronto', 'Ottawa', 'Vancouver']],
        'bresil' => ['Bresil', 'br', 'Ameriques', ['Rio de Janeiro', 'Sao Paulo', 'Brasilia']],
        'mexique' => ['Mexique', 'mx', 'Ameriques', ['Mexico', 'Cancun', 'Guadalajara']],
        'cuba' => ['Cuba', 'cu', 'Ameriques', ['La Havane', 'Varadero']],
        'emirats' => ['Emirats arabes unis', 'ae', 'Asie & Moyen-Orient', ['Dubai', 'Abou Dabi']],
        'qatar' => ['Qatar', 'qa', 'Asie & Moyen-Orient', ['Doha']],
        'arabie-saoudite' => ['Arabie saoudite', 'sa', 'Asie & Moyen-Orient', ['Riyad', 'Djeddah', 'La Mecque']],
        'chine' => ['Chine', 'cn', 'Asie & Moyen-Orient', ['Pekin', 'Shanghai', 'Shenzhen', 'Hong Kong']],
        'japon' => ['Japon', 'jp', 'Asie & Moyen-Orient', ['Tokyo', 'Kyoto', 'Osaka']],
        'thailande' => ['Thailande', 'th', 'Asie & Moyen-Orient', ['Bangkok', 'Phuket', 'Chiang Mai']],
        'indonesie' => ['Indonesie', 'id', 'Asie & Moyen-Orient', ['Bali', 'Jakarta']],
        'inde' => ['Inde', 'in', 'Asie & Moyen-Orient', ['New Delhi', 'Bombay', 'Agra', 'Jaipur']],
    ];

    /** Un pays au format affichable, ou null. */
    public static function find(?string $slug): ?array
    {
        $c = self::COUNTRIES[$slug] ?? null;

        return $c ? self::format($slug, $c) : null;
    }

    /** Tous les pays, au format affichable. */
    public static function all(): array
    {
        return collect(self::COUNTRIES)->map(fn ($c, $slug) => self::format($slug, $c))->values()->all();
    }

    /** Le pays d'une ville (pour relier "Decouvrir le monde" a son pays). */
    public static function forCity(string $city): ?array
    {
        $needle = mb_strtolower(trim($city));
        foreach (self::COUNTRIES as $slug => $c) {
            foreach ($c[3] as $v) {
                if (mb_strtolower($v) === $needle) {
                    return self::format($slug, $c);
                }
            }
        }

        return null;
    }

    private static function format(string $slug, array $c): array
    {
        return [
            'slug' => $slug,
            'name' => $c[0],
            'code' => $c[1],
            'continent' => $c[2],
            'cities' => $c[3],
            'flag' => 'https://flagcdn.com/w320/'.$c[1].'.png',
            'local' => $slug === self::LOCAL,
            'href' => $slug === self::LOCAL ? '/divertissement' : '/tourisme/pays/'.$slug,
        ];
    }
}
