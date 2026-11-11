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
        'concessionnaire' => 'Concessionnaires & vendeurs auto',
        'restaurant' => 'Restaurants',
        'bar_lounge' => 'Bars & lounges',
        'night_club' => 'Night clubs',
        'cinema' => 'Cinemas',
        'salle_spectacle' => 'Salles de concert & spectacles',
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
        'concessionnaire' => 'voiture',
        'restaurant' => 'divertissement',
        'night_club' => 'divertissement',
        'cinema' => 'divertissement',
        'salle_spectacle' => 'divertissement',
        'site_touristique' => 'divertissement',
        'bar_lounge' => 'divertissement',
        'supermarche' => 'marketplace',
        'ecommerce' => 'marketplace',
    ];

    protected $fillable = [
        'name', 'slug', 'category', 'city', 'district', 'address', 'country', 'stars',
        'description', 'services', 'opening_hours', 'price_range', 'phone', 'whatsapp',
        'email', 'website', 'latitude', 'longitude', 'image', 'gallery', 'is_featured', 'is_active', 'preview_image',
    ];

    // Photo du mode presentation : jamais envoyee au public (voir PreviewController).
    protected $hidden = ['preview_image'];

    protected $appends = ['image_url', 'image_is_illustration', 'gallery_urls', 'category_label', 'module'];

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
        // Pas encore de photo officielle : illustration (garages, concessionnaires).
        return AttractionPark::resolveUrl($this->image) ?? \App\Support\PartnerPhotos::for($this);
    }

    /** true : la photo n'est pas celle de la societe (illustration en attendant ses photos). */
    public function getImageIsIllustrationAttribute(): bool
    {
        return ! $this->image && $this->image_url !== null;
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
