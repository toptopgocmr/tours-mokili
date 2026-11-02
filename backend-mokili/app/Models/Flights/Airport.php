<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = ['iata_code', 'name', 'city', 'country_code', 'country_name', 'timezone', 'is_active', 'popularity'];

    protected $appends = ['label'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->city} ({$this->iata_code}) - {$this->name}";
    }

    public static function lookup(?string $code): ?self
    {
        return $code ? static::where('iata_code', strtoupper($code))->first() : null;
    }
}
