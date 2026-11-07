<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Claim extends Model
{
    protected $table = 'freight_claims';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $c) => $c->reference ??= 'FRT-REC-'.strtoupper(Str::random(6)));
    }

    public function order()
    {
        return $this->belongsTo(FreightOrder::class, 'freight_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(ClaimMessage::class)->orderBy('id');
    }
}
