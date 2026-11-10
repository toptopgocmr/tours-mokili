<?php

namespace App\Services\Tourisme;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sites touristiques du monde entier (facon Boas "Site touristique", mais
 * pour toutes les villes) : lieux remarquables autour d'une ville, avec
 * photo et resume, d'apres Wikipedia (API publique, contenus CC BY-SA).
 * Resultats gardes en cache 7 jours par ville.
 */
class WorldPlaces
{
    public const CATEGORIES = [
        'sites' => 'Sites touristiques',
        'musees' => 'Musees',
        'monuments' => 'Monuments & lieux de culte',
        'nature' => 'Parcs & nature',
        'loisirs' => 'Lieux de loisirs',
        'plages' => 'Plages',
    ];

    // Villes proposees en raccourci (comme les onglets Brazzaville / Pointe-Noire de Boas).
    public const CITIES = [
        'Afrique' => ['Brazzaville', 'Pointe-Noire', 'Kinshasa', 'Douala', 'Libreville', 'Abidjan', 'Dakar', 'Marrakech', 'Le Caire', 'Le Cap', 'Zanzibar', 'Kigali'],
        'Europe' => ['Paris', 'Londres', 'Rome', 'Barcelone', 'Istanbul', 'Bruxelles', 'Amsterdam', 'Lisbonne'],
        'Ameriques' => ['New York', 'Montreal', 'Miami', 'Rio de Janeiro', 'Mexico'],
        'Asie & Moyen-Orient' => ['Dubai', 'Doha', 'Pekin', 'Tokyo', 'Bangkok', 'Bali'],
    ];

    private const KEYWORDS = [
        'musees' => ['musee', 'museum', 'galerie', 'gallery', 'exposition'],
        'monuments' => ['cathedrale', 'basilique', 'eglise', 'mosquee', 'temple', 'monument', 'palais', 'chateau', ' fort ', ' tour ', ' arc ', 'memorial', 'mausolee', 'statue', ' pont ', 'synagogue', 'pagode'],
        'nature' => ['parc', 'jardin', 'foret', 'reserve', 'chute', 'cascade', ' lac ', 'riviere', 'fleuve', 'montagne', ' mont ', 'colline', ' ile ', ' iles ', 'zoo', 'botanique', 'gorges'],
        'loisirs' => ['stade', 'marche', 'centre commercial', 'theatre', 'opera', 'cinema', 'casino', 'aquarium', 'parc d\'attractions', 'salle', 'arena', 'port de ', 'place '],
        'plages' => ['plage', 'beach', 'baie', 'cote', 'lagune'],
    ];

    public function search(string $city): array
    {
        $city = trim($city);
        if ($city === '') {
            return ['city' => null, 'places' => [], 'error' => null];
        }

        return Cache::remember('world_places:'.Str::slug($city), now()->addDays(7), fn () => $this->fetch($city));
    }

    protected function http()
    {
        return Http::timeout(12)->retry(1, 300)->withHeaders([
            'User-Agent' => 'MOKILI-TOUR/1.0 ('.config('app.url').')',
        ])->baseUrl('https://fr.wikipedia.org/w/api.php');
    }

    protected function fetch(string $city): array
    {
        try {
            // 1) La ville : coordonnees et photo.
            $r = $this->http()->get('', [
                'action' => 'query', 'format' => 'json', 'redirects' => 1, 'titles' => $city,
                'prop' => 'coordinates|pageimages|extracts', 'piprop' => 'thumbnail', 'pithumbsize' => 1280,
                'exintro' => 1, 'explaintext' => 1, 'exsentences' => 2,
            ])->json('query.pages', []);
            $page = collect($r)->first();
            $coord = $page['coordinates'][0] ?? null;
            if (! $coord) {
                return ['city' => null, 'places' => [], 'error' => 'Ville introuvable. Verifiez l\'orthographe (ex. "Pointe-Noire", "New York").'];
            }

            // 2) Lieux remarquables dans un rayon de 10 km.
            $pages = $this->http()->get('', [
                'action' => 'query', 'format' => 'json', 'generator' => 'geosearch',
                'ggscoord' => $coord['lat'].'|'.$coord['lon'], 'ggsradius' => 10000, 'ggslimit' => 80,
                'prop' => 'pageimages|description|coordinates|extracts', 'piprop' => 'thumbnail', 'pithumbsize' => 640,
                'exintro' => 1, 'explaintext' => 1, 'exsentences' => 2, 'exlimit' => 'max',
            ])->json('query.pages', []);

            $places = collect($pages)
                ->filter(fn ($p) => ! empty($p['thumbnail']['source']) && ($p['title'] ?? '') !== ($page['title'] ?? ''))
                ->reject(fn ($p) => $this->isNotAPlace($p) || (empty($p['description']) && mb_strlen($p['extract'] ?? '') < 80))
                ->map(fn ($p) => [
                    'id' => $p['pageid'],
                    'name' => $p['title'],
                    'description' => Str::limit(trim($p['extract'] ?? ($p['description'] ?? '')), 220),
                    'image' => $p['thumbnail']['source'],
                    'lat' => $p['coordinates'][0]['lat'] ?? null,
                    'lon' => $p['coordinates'][0]['lon'] ?? null,
                    'category' => $this->categorize(($p['title'] ?? '').' '.($p['description'] ?? '').' '.($p['extract'] ?? '')),
                    'url' => 'https://fr.wikipedia.org/?curid='.$p['pageid'],
                ])
                ->sortBy(fn ($p) => $p['category'] === 'sites' ? 1 : 0)
                ->values()->take(48)->all();

            return [
                'city' => [
                    'name' => $page['title'],
                    'summary' => trim($page['extract'] ?? ''),
                    'image' => $page['thumbnail']['source'] ?? null,
                    'lat' => $coord['lat'], 'lon' => $coord['lon'],
                ],
                'places' => $places,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['city' => null, 'places' => [], 'error' => 'Le service de decouverte est momentanement indisponible. Reessayez dans un instant.'];
        }
    }

    public function categorize(string $text): string
    {
        $t = ' '.preg_replace('/[^a-z0-9\']+/', ' ', Str::lower(Str::ascii($text))).' ';
        foreach (self::KEYWORDS as $cat => $words) {
            foreach ($words as $w) {
                if (str_contains($t, Str::ascii($w))) {
                    return $cat;
                }
            }
        }

        return 'sites';
    }

    /** Ecarte les pages qui ne sont pas des lieux a visiter (quartiers, ecoles, ambassades...). */
    protected function isNotAPlace(array $p): bool
    {
        $t = Str::lower(Str::ascii(($p['title'] ?? '').' '.($p['description'] ?? '')));
        foreach (['ambassade', 'ecole', 'lycee', 'universite', 'hopital', 'clinique', 'arrondissement', 'quartier', 'commune', 'ministere', 'banque', 'societe', 'entreprise', 'club de football', 'aeroport', 'gare', 'station de', 'ligne ', 'route ', 'avenue', 'rue ', 'boulevard', 'bataille', 'massacre', 'prison', 'siege', 'accident', 'catastrophe', 'attentat', 'projet de', 'cinq etoiles', 'radisson', 'hilton', 'centre hospitalier', 'district', 'village', 'ville de', 'capitale'] as $w) {
            if (str_contains($t, $w)) {
                return true;
            }
        }

        return false;
    }
}
