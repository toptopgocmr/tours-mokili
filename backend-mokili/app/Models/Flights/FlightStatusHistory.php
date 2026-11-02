<?php

namespace App\Models\Flights;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FlightStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['flight_booking_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
