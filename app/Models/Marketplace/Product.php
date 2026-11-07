<?php

namespace App\Models\Marketplace;

use App\Models\Booking;
use App\Models\Concerns\HasPublishingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// MARKETPLACE module (skeleton).
class Product extends Model
{
    use HasPublishingStatus;

    protected $table = 'marketplace_products';

    protected $fillable = [
        'seller_id', 'title', 'listing_type', 'slug', 'description', 'category', 'price', 'negotiable',
        'currency', 'stock', 'condition', 'city', 'district', 'country', 'contact_name', 'contact_phone',
        'contact_whatsapp', 'image', 'gallery', 'views', 'sold_at', 'is_active',
        'status', 'rejection_reason',
    ];

    protected $appends = ['image_url', 'gallery_urls', 'category_label', 'listing_type_label'];

    public function getCategoryLabelAttribute(): ?string
    {
        return $this->category ? (config('catalogue.marketplace.categories')[$this->category] ?? $this->category) : null;
    }

    public function getListingTypeLabelAttribute(): string
    {
        return config('catalogue.marketplace.listing_types')[$this->listing_type ?? 'vente'] ?? 'Je vends';
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])->map(fn ($p) => str_starts_with($p, 'http') ? $p : \Illuminate\Support\Facades\Storage::disk('public')->url($p))->values()->all();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'negotiable' => 'boolean',
            'gallery' => 'array',
            'sold_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            $product->slug ??= Str::slug($product->title).'-'.Str::random(6);
        });
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function orders()
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
