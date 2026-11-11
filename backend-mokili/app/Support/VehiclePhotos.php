<?php

namespace App\Support;

/**
 * Photo d'une marque / d'un modele (config/vehicle_photos.php). Sert de
 * photo d'illustration aux annonces publiees sans photo.
 */
class VehiclePhotos
{
    public static function for(?string $brand, ?string $model = null): ?string
    {
        if (! $brand) {
            return null;
        }
        $all = config('vehicle_photos', []);
        $key = collect(array_keys($all))->first(fn ($b) => strcasecmp($b, trim($brand)) === 0);
        if (! $key) {
            return null;
        }
        $photos = $all[$key];
        $model = trim((string) $model);

        if ($model !== '') {
            foreach ($photos as $m => $url) {
                if ($m !== '_' && strcasecmp($m, $model) === 0) {
                    return $url;
                }
            }
            // "Land Cruiser Prado VX" -> "Land Cruiser Prado", "T2 i-DM" -> "T2"
            $best = null;
            foreach ($photos as $m => $url) {
                if ($m !== '_' && stripos($model, $m) === 0 && (! $best || strlen($m) > strlen($best[0]))) {
                    $best = [$m, $url];
                }
            }
            if ($best) {
                return $best[1];
            }
        }

        return $photos['_'] ?? null;
    }
}
