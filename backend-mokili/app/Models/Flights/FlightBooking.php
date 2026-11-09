<?php

namespace App\Models\Flights;

use App\Models\Booking;
use App\Models\User;
use App\Services\Flights\FlightStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Dossier de billetterie aerienne. Le paiement passe par le Booking
 * generique (checkout commun) ; ce dossier porte la machine a etats
 * propre a l'aerien : paiement confirme, reservation (PNR) et billet emis
 * sont trois evenements distincts.
 */
class FlightBooking extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'agency_id', 'flight_offer_id', 'flight_supplier_id', 'booking_id',
        'status', 'after_sales_status', 'refund_status', 'needs_attention', 'attention_reason', 'pnr',
        'supplier_booking_ref', 'contact_name', 'contact_email', 'contact_phone', 'trip_type', 'origin',
        'destination', 'depart_date', 'return_date', 'itinerary', 'fare_rules', 'passenger_count',
        'supplier_cost', 'markup_amount', 'service_fee', 'commission_amount', 'total_amount',
        'refunded_amount', 'currency', 'price_confirmed_at', 'paid_at', 'booked_at', 'ticketing_deadline',
        'ticketed_at', 'cancelled_at', 'refunded_at', 'last_supplier_check_at', 'failure_reason',
    ];

    protected $appends = ['status_label', 'payment_state', 'booking_state', 'ticket_state', 'title'];

    protected function casts(): array
    {
        return [
            'itinerary' => 'array',
            'fare_rules' => 'array',
            'needs_attention' => 'boolean',
            'depart_date' => 'date',
            'return_date' => 'date',
            'supplier_cost' => 'decimal:2',
            'markup_amount' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'price_confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
            'booked_at' => 'datetime',
            'ticketing_deadline' => 'datetime',
            'ticketed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'last_supplier_check_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $b) {
            $b->reference ??= 'MKF-'.strtoupper(Str::random(7));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
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

    public function histories()
    {
        return $this->hasMany(FlightStatusHistory::class)->orderBy('id');
    }

    public function supplierLogs()
    {
        return $this->hasMany(FlightSupplierLog::class)->orderBy('id');
    }

    public function afterSales()
    {
        return $this->hasMany(FlightAfterSale::class)->latest();
    }

    /** Separation stricte : un client ne voit que ses dossiers, une agence ceux de ses clients. */
    public function scopeVisibleTo($query, User $user)
    {
        if (in_array($user->role, ['admin', 'agent'], true)) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('agency_id', $user->id));
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || in_array($this->status, FlightStatus::PAID, true);
    }

    public function getStatusLabelAttribute(): string
    {
        return FlightStatus::label($this->status);
    }

    public function getTitleAttribute(): string
    {
        if ($this->trip_type === 'multicity' && is_array($this->itinerary) && $this->itinerary) {
            $itinerary = array_values($this->itinerary);
            $stops = array_map(fn ($s) => $s['origin'] ?? '?', $itinerary);
            $stops[] = $itinerary[count($itinerary) - 1]['destination'] ?? '?';

            return 'Vol '.implode(' -> ', $stops);
        }

        $arrow = $this->trip_type === 'roundtrip' ? ' <-> ' : ' -> ';

        return 'Vol '.$this->origin.$arrow.$this->destination;
    }

    // Trois statuts suivis separement (annexe du cahier des charges).
    public function getPaymentStateAttribute(): string
    {
        return match (true) {
            $this->status === FlightStatus::REFUNDED => 'refunded',
            $this->paid_at !== null => 'paid',
            $this->status === FlightStatus::PAYMENT_PENDING => 'pending',
            default => 'unpaid',
        };
    }

    public function getBookingStateAttribute(): string
    {
        return match (true) {
            $this->pnr !== null && $this->booked_at !== null => 'confirmed',
            $this->status === FlightStatus::BOOKING_PENDING => 'pending',
            $this->status === FlightStatus::FAILED && $this->booked_at === null && $this->paid_at !== null => 'failed',
            default => 'none',
        };
    }

    public function getTicketStateAttribute(): string
    {
        return match (true) {
            $this->ticketed_at !== null => 'issued',
            $this->status === FlightStatus::TICKETING_PENDING => 'pending',
            $this->status === FlightStatus::FAILED && $this->booked_at !== null => 'failed',
            default => 'none',
        };
    }
}
