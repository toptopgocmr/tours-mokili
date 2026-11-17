<?php

namespace App\Support;

/**
 * ANNUAIRE : pays proposes (code ISO 2 lettres => nom). Reprend les pays du
 * paiement (config payments.country_names) et ajoute d'autres pays d'affaires.
 * Pour ajouter un pays : une ligne dans EXTRA.
 */
class DirectoryCountries
{
    private const EXTRA = [
        'DE' => 'Allemagne', 'IT' => 'Italie', 'ES' => 'Espagne', 'PT' => 'Portugal', 'NL' => 'Pays-Bas',
        'LU' => 'Luxembourg', 'MC' => 'Monaco', 'SE' => 'Suede', 'NO' => 'Norvege', 'DK' => 'Danemark',
        'IE' => 'Irlande', 'AT' => 'Autriche', 'PL' => 'Pologne', 'RU' => 'Russie', 'GR' => 'Grece',
        'BR' => 'Bresil', 'MX' => 'Mexique', 'AR' => 'Argentine', 'CU' => 'Cuba', 'HT' => 'Haiti',
        'JP' => 'Japon', 'KR' => 'Coree du Sud', 'SG' => 'Singapour', 'MY' => 'Malaisie', 'TH' => 'Thailande',
        'VN' => 'Vietnam', 'ID' => 'Indonesie', 'SA' => 'Arabie saoudite', 'QA' => 'Qatar', 'KW' => 'Koweit',
        'LB' => 'Liban', 'IL' => 'Israel', 'IR' => 'Iran', 'PK' => 'Pakistan', 'AU' => 'Australie',
        'LY' => 'Libye', 'SD' => 'Soudan', 'ER' => 'Erythree', 'MU' => 'Maurice', 'SC' => 'Seychelles',
    ];

    /** @return array<string,string> code => nom, Congo en tete puis ordre alphabetique. */
    public static function all(): array
    {
        $list = array_merge(config('payments.country_names', []), self::EXTRA);
        $first = ['CG' => $list['CG'] ?? 'Congo-Brazzaville'];
        unset($list['CG']);
        asort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $first + $list;
    }

    public static function name(?string $code): string
    {
        $code = strtoupper((string) $code);

        return self::all()[$code] ?? $code;
    }
}
