<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuoteRequest extends Model
{
    protected $table = 'freight_quote_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'dangerous_goods' => 'boolean',
            'weight_kg' => 'decimal:2',
            'volume_m3' => 'decimal:3',
            'declared_value' => 'decimal:2',
            'ready_date' => 'date',
            'desired_pickup_date' => 'date',
            'desired_delivery_date' => 'date',
            'partner_ack_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $r) => $r->reference ??= 'FRT-DEM-'.strtoupper(Str::random(6)));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function offer()
    {
        return $this->belongsTo(FreightOffer::class, 'freight_offer_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'quote_request_id')->orderByDesc('version');
    }

    public function documents()
    {
        return $this->morphMany(FreightDocument::class, 'documentable');
    }
}
