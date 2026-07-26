<?php

namespace App\Models\Marketplace;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// MARKETPLACE module (skeleton).
class Product extends Model
{
    protected $table = 'marketplace_products';

    protected $fillable = [
        'seller_id', 'title', 'slug', 'description', 'category', 'price',
        'currency', 'stock', 'condition', 'city', 'country', 'image', 'is_active',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
