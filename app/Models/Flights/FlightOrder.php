<?php

namespace App\Models\Flights;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

/**
 * Dossier de vol. Le statut ne change JAMAIS directement : toute
 * transition passe par FlightStateMachine (controle serveur + historique).
 */
class FlightOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_total' => 'decimal:2',
            'supplier_cost' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'is_anomaly' => 'boolean',
            'payment_confirmed_at' => 'datetime',
            'booked_at' => 'datetime',
            'ticketed_at' => 'datetime',
        ];
    }

    // Le cout fournisseur et la commission restent internes.
    protected $hidden = ['supplier_cost', 'commission_amount', 'supplier_order_id'];

    protected static function booted(): void
    {
        static::creating(function (FlightOrder $order) {
            $order->reference ??= 'MKT-VOL-'.strtoupper(Str::random(6));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offer()
    {
        return $this->belongsTo(FlightOffer::class, 'flight_offer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(FlightSupplier::class, 'flight_supplier_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function passengers()
    {
        return $this->hasMany(FlightPassenger::class);
    }

    public function tickets()
    {
        return $this->hasMany(FlightTicket::class);
    }

    public function history()
    {
        return $this->hasMany(FlightStatusHistory::class)->orderBy('id');
    }

    public function operations()
    {
        return $this->hasMany(FlightOperation::class);
    }

    public function refunds()
    {
        return $this->hasMany(FlightRefund::class);
    }

    public function segmentStatuses()
    {
        return $this->hasMany(FlightSegmentStatus::class)->orderBy('flight_date')->orderBy('id');
    }

    public function commission()
    {
        return $this->hasOne(FlightCommission::class);
    }

    public function isTicketed(): bool
    {
        return $this->status === FlightStatus::TICKETED;
    }
}
