<?php

namespace App\Models\Voyage;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * VOYAGE module - pilot module, fully implemented (CRUD + booking + checkout).
 */
class TravelOffer extends Model
{
    use HasFactory;

    /** Moyens de transport d'un circuit (les vols sont toujours "avion"). */
    public const TRANSPORT_MODES = [
        'avion' => 'Avion',
        'bus' => 'Bus / car',
        'bateau' => 'Bateau',
        'canot' => 'Canot rapide / pirogue',
        'train' => 'Train',
        'taxi' => 'Taxi interurbain',
        'voiture' => 'Voiture / 4x4',
        'combine' => 'Combine (plusieurs transports)',
    ];

    protected $fillable = [
        'title', 'slug', 'type', 'transport_mode', 'description', 'origin_city', 'origin_country',
        'departure_point', 'duration_label', 'trip_scope', 'origin_department', 'destination_department',
        'destination_city', 'destination_country', 'airline', 'departure_at', 'arrival_at',
        'return_at', 'price', 'discount_percent', 'currency', 'seats_available',
        'image', 'is_featured', 'is_active', 'owner_id', 'promo_label', 'promo_ends_at',
        'arrival_point', 'amenities',
    ];

    // Exposed on every JSON/Inertia response (web + API) so both
    // frontends can read the post-discount price directly.
    protected $appends = ['discounted_price', 'image_url', 'transport_mode_label', 'trip_scope_label'];

    // Services a bord proposes aux transporteurs.
    public const AMENITIES = [
        'wifi' => 'Wi-Fi', 'prises' => 'Prises electriques', 'clim' => 'Climatisation', 'toilettes' => 'Toilettes',
        'bagages' => 'Bagage en soute inclus', 'collation' => 'Collation', 'tv' => 'Ecrans / TV', 'gilets' => 'Gilets de sauvetage',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'return_at' => 'datetime',
            'promo_ends_at' => 'datetime',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TravelOffer $offer) {
            $offer->slug ??= Str::slug($offer->title).'-'.Str::random(6);
        });
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    /** Une promotion terminee (promo_ends_at passe) ne s'applique plus. */
    public function getDiscountPercentAttribute($value): int
    {
        $end = $this->attributes['promo_ends_at'] ?? null;

        return $end && \Carbon\Carbon::parse($end)->isPast() ? 0 : (int) $value;
    }

    public function getDiscountedPriceAttribute(): float
    {
        return round($this->price * (1 - $this->discount_percent / 100), 2);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getTransportModeLabelAttribute(): ?string
    {
        return $this->transport_mode ? (self::TRANSPORT_MODES[$this->transport_mode] ?? $this->transport_mode) : null;
    }

    public function getTripScopeLabelAttribute(): ?string
    {
        return $this->trip_scope ? (config('catalogue.voyage.trip_scopes')[$this->trip_scope] ?? $this->trip_scope) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
