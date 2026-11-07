<?php

namespace App\Models\Guide;

use App\Models\Loisirs\AttractionPark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Societe partenaire / adresse utile (hotel, compagnie aerienne, transport,
 * taxi, supermarche, restaurant, bar, boutique en ligne), affichee dans la
 * rubrique correspondant a son activite (voir MODULES). Geree depuis
 * /admin/partenaires.
 */
class Business extends Model
{
    public const CATEGORIES = [
        'hotel' => 'Hotels',
        'compagnie_aerienne' => 'Compagnies aeriennes',
        'transport_routier' => 'Transport routier',
        'taxi_vtc' => 'Taxis & VTC',
        'canot_rapide' => 'Canots rapides & fluvial',
        'transport_ferroviaire' => 'Train',
        'supermarche' => 'Supermarches',
        'garage' => 'Garages & mecanique',
        'restaurant' => 'Restaurants',
        'bar_lounge' => 'Bars & lounges',
        'night_club' => 'Night clubs',
        'site_touristique' => 'Visites & sites touristiques',
        'ecommerce' => 'Boutiques en ligne',
    ];

    /** Rubrique MOKILI TOUR ou apparait chaque type de societe. */
    public const MODULES = [
        'hotel' => 'logement',
        'compagnie_aerienne' => 'voyage',
        'transport_routier' => 'voyage',
        'canot_rapide' => 'voyage',
        'transport_ferroviaire' => 'voyage',
        'taxi_vtc' => 'voiture',
        'garage' => 'voiture',
        'restaurant' => 'divertissement',
        'night_club' => 'divertissement',
        'site_touristique' => 'divertissement',
        'bar_lounge' => 'divertissement',
        'supermarche' => 'marketplace',
        'ecommerce' => 'marketplace',
    ];

    protected $fillable = [
        'name', 'slug', 'category', 'city', 'district', 'address', 'country', 'stars',
        'description', 'services', 'opening_hours', 'price_range', 'phone', 'whatsapp',
        'email', 'website', 'latitude', 'longitude', 'image', 'gallery', 'is_featured', 'is_active',
    ];

    protected $appends = ['image_url', 'gallery_urls', 'category_label', 'module'];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'gallery' => 'array',
            'stars' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $b) {
            $b->slug ??= Str::slug($b->name).'-'.Str::lower(Str::random(4));
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Societes a afficher dans une rubrique (logement, voyage, voiture...). */
    public function scopeForModule($query, string $module)
    {
        return $query->whereIn('category', array_keys(array_filter(self::MODULES, fn ($m) => $m === $module)));
    }

    public function getModuleAttribute(): ?string
    {
        return self::MODULES[$this->category] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return AttractionPark::resolveUrl($this->image);
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])->map(fn ($p) => AttractionPark::resolveUrl($p))->filter()->values()->all();
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }
}
