<?php

namespace App\Support;

use App\Models\Guide\Business;

/** Photo d'illustration d'un garage / concessionnaire sans photo officielle. */
class PartnerPhotos
{
    public static function for(Business $b): ?string
    {
        $cfg = config('partner_photos', []);
        $services = collect($b->services ?? [])->map(fn ($s) => (string) $s);

        if ($b->category === 'concessionnaire') {
            foreach ($services as $s) {
                if (isset($cfg['brands'][$s])) {
                    return $cfg['brands'][$s];
                }
                if ($url = VehiclePhotos::for($s)) {
                    return $url;
                }
            }

            return $cfg['concessionnaire_default'] ?? null;
        }

        if ($b->category === 'garage') {
            foreach ($services as $s) {
                if (isset($cfg['garage_services'][$s])) {
                    return $cfg['garage_services'][$s];
                }
            }
            $pool = $cfg['garage_default'] ?? [];

            return $pool ? $pool[((int) $b->id) % count($pool)] : null;
        }

        $name = mb_strtolower((string) $b->name);
        foreach ($cfg['name_keywords'] ?? [] as $word => $url) {
            if (str_contains($name, $word)) {
                return $url;
            }
        }

        $pool = $cfg['categories'][$b->category] ?? [];
        if ($pool) {
            return $pool[((int) $b->id) % count($pool)];
        }

        if ($b->category === 'site_touristique') {
            foreach ($cfg['site_keywords'] ?? [] as $word => $url) {
                if (str_contains($name, $word)) {
                    return $url;
                }
            }

            if (isset($cfg['site_cities'][$b->city])) {
                return $cfg['site_cities'][$b->city];
            }
        }

        // Annuaire : toute societe a une photo (illustration de sa ville).
        return $cfg['fallback_cities'][$b->city] ?? ($cfg['fallback'] ?? null);
    }
}
