<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

/** Passager d'un dossier. Le numero de document est chiffre en base. */
class FlightPassenger extends Model
{
    public const TYPES = ['ADT' => 'Adulte', 'CHD' => 'Enfant (2-11 ans)', 'INF' => 'Bebe (< 2 ans)'];

    protected $fillable = [
        'flight_booking_id', 'type', 'title', 'first_name', 'last_name', 'gender', 'birth_date',
        'nationality', 'document_type', 'document_number', 'document_expiry', 'document_country',
        'email', 'phone',
    ];

    protected $hidden = ['document_number'];

    protected $appends = ['full_name', 'document_masked'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'document_expiry' => 'date',
            'document_number' => 'encrypted',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function ticket()
    {
        return $this->hasOne(FlightTicket::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->title ? $this->title.' ' : '').strtoupper($this->last_name).' '.$this->first_name);
    }

    public function getDocumentMaskedAttribute(): ?string
    {
        $n = $this->document_number;

        return $n ? str_repeat('*', max(strlen($n) - 3, 0)).substr($n, -3) : null;
    }
}
