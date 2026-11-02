<?php

namespace App\Models\Fret;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// FRET module - a carrier/partner's published freight service (route,
// mode, price per kg). Not to be confused with Shipment, which tracks an
// actual customer shipment once created.
class FreightOffer extends Model
{
    use HasPublishingStatus;

    protected $fillable = [
        'carrier_id', 'title', 'slug', 'description', 'mode', 'package_types', 'transit_time',
        'origin_city', 'origin_country', 'destination_city', 'destination_country',
        'price_per_kg', 'currency', 'capacity_kg', 'image', 'is_active',
        'status', 'rejection_reason',
    ];

    protected $appends = ['image_url', 'scope', 'mode_label', 'package_type_labels'];

    /** National si depart et arrivee sont dans le meme pays. */
    public function getScopeAttribute(): string
    {
        return strtoupper((string) $this->origin_country) === strtoupper((string) $this->destination_country) ? 'national' : 'international';
    }

    public function getModeLabelAttribute(): string
    {
        return config('catalogue.fret.modes')[$this->mode] ?? (string) $this->mode;
    }

    public function getPackageTypeLabelsAttribute(): array
    {
        $labels = config('catalogue.fret.package_types');

        return collect($this->package_types ?: array_keys($labels))->map(fn ($k) => $labels[$k] ?? $k)->values()->all();
    }

    /** Filtre national / international (meme pays ou non). */
    public function scopeForScope($query, ?string $scope)
    {
        return match ($scope) {
            'national' => $query->whereColumn('origin_country', 'destination_country'),
            'international' => $query->whereColumn('origin_country', '!=', 'destination_country'),
            default => $query,
        };
    }

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'package_types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $offer) {
            $offer->slug ??= Str::slug($offer->title).'-'.Str::random(6);
        });
    }

    public function carrier()
    {
        return $this->belongsTo(User::class, 'carrier_id');
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Demo listings store a full external photo URL directly; real
        // partner uploads store a Storage-disk-relative path.
        return str_starts_with($this->image, 'http')
            ? $this->image
            : Storage::disk('public')->url($this->image);
    }
}
