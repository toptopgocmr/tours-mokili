<?php

namespace App\Models\Flights;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Demande apres-vente : annulation, modification ou remboursement. */
class FlightAfterSale extends Model
{
    public const TYPES = [
        'cancellation' => 'Annulation',
        'change' => 'Modification (date, nom...)',
        'refund' => 'Remboursement',
    ];

    public const STATUSES = [
        'submitted' => 'Demande recue',
        'in_review' => 'En cours de traitement',
        'approved' => 'Acceptee',
        'rejected' => 'Refusee',
        'processed' => 'Traitee',
    ];

    public const REFUND_STATUSES = [
        'pending' => 'Remboursement en cours',
        'refunded' => 'Rembourse',
        'failed' => 'Remboursement en echec',
    ];

    protected $fillable = [
        'flight_booking_id', 'user_id', 'type', 'reason', 'attachments', 'status', 'requested_amount',
        'supplier_fee', 'psp_fee', 'penalty', 'refundable_amount', 'estimated_delay', 'refund_status',
        'refund_reference', 'admin_notes', 'processed_by', 'processed_at',
    ];

    protected $appends = ['type_label', 'status_label', 'refund_status_label'];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'requested_amount' => 'decimal:2',
            'supplier_fee' => 'decimal:2',
            'psp_fee' => 'decimal:2',
            'penalty' => 'decimal:2',
            'refundable_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRefundStatusLabelAttribute(): ?string
    {
        return $this->refund_status ? (self::REFUND_STATUSES[$this->refund_status] ?? $this->refund_status) : null;
    }
}
