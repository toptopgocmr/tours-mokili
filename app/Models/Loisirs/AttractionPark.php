<?php

namespace App\Models\Loisirs;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * LOISIRS module - parcs d'attractions et sites de loisirs du
 * Congo-Brazzaville (Brazzaville, Pointe-Noire, parcs nationaux...).
 * Gere par l'equipe MOKILI TOUR depuis le back-office (/admin/loisirs).
 */
class AttractionPark extends Model
{
    public const CATEGORIES = [
        'parc_attractions' => "Parc d'attractions",
        'parc_aquatique' => 'Parc aquatique',
        'zoo' => 'Zoo & parc animalier',
        'aire_de_jeux' => 'Aire de jeux enfants',
        'ile_loisirs' => 'Ile & plage de loisirs',
        'parc_naturel' => 'Parc naturel & reserve',
    ];

    public const CITIES = ['Brazzaville', 'Pointe-Noire', 'Kintele', 'Dolisie', 'Oyo', 'Ouesso'];

    protected $fillable = [
        'name', 'slug', 'category', 'city', 'district', 'address', 'country',
        'description', 'activities', 'opening_hours', 'price_adult', 'price_child',
        'currency', 'min_age', 'phone', 'website', 'latitude', 'longitude',
        'image', 'gallery', 'is_featured', 'is_active',
    ];

    protected $appends = ['image_url', 'gallery_urls', 'category_label', 'is_bookable'];

    protected function casts(): array
    {
        return [
            'activities' => 'array',
            'gallery' => 'array',
            'price_adult' => 'decimal:2',
            'price_child' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $park) {
            $park->slug ??= Str::slug($park->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getImageUrlAttribute(): ?string
    {
        return self::resolveUrl($this->image);
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])->map(fn ($p) => self::resolveUrl($p))->filter()->values()->all();
    }

    /** URL complete, chemin public (/images/...) ou fichier du disque "public". */
    public static function resolveUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Str::startsWith($path, '/') ? url($path) : Storage::disk('public')->url($path);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    /** Un parc n'est reservable en ligne que si un tarif adulte est defini. */
    public function getIsBookableAttribute(): bool
    {
        return $this->price_adult !== null && (float) $this->price_adult > 0;
    }

    /** Prix enfant effectif (retombe sur le tarif adulte s'il n'est pas defini). */
    public function childPrice(): float
    {
        return (float) ($this->price_child ?? $this->price_adult ?? 0);
    }
}
