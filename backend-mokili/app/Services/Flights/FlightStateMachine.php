<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * Machine a etats explicite (cahier des charges, section 11). Les
 * transitions sont controlees cote serveur, sous verrou, et historisees.
 */
class FlightStateMachine
{
    public function transition(FlightBooking $booking, string $to, array $attributes = [], ?string $note = null, array $context = [], ?string $actorType = null): FlightBooking
    {
        return DB::transaction(function () use ($booking, $to, $attributes, $note, $context, $actorType) {
            $locked = FlightBooking::query()->lockForUpdate()->findOrFail($booking->id);
            $from = $locked->status;

            if ($from === $to) {
                if ($attributes) {
                    $locked->update($attributes);
                }

                return $locked;
            }

            if (! FlightStatus::canTransition($from, $to)) {
                throw new InvalidFlightTransition($from, $to);
            }

            $locked->update(['status' => $to] + $attributes);

            FlightStatusHistory::create([
                'flight_booking_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor_type' => $actorType ?? (auth()->check() ? (in_array(auth()->user()->role, ['admin', 'agent'], true) ? 'admin' : 'client') : 'system'),
                'actor_id' => auth()->id(),
                'note' => $note ? mb_substr($note, 0, 250) : null,
                'context' => $context ?: null,
            ]);

            $booking->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /** Transition si autorisee, sinon ne fait rien (callbacks rejoues, webhooks en double). */
    public function tryTransition(FlightBooking $booking, string $to, array $attributes = [], ?string $note = null, array $context = [], ?string $actorType = null): bool
    {
        $current = $booking->fresh()->status;
        if ($current !== $to && ! FlightStatus::canTransition($current, $to)) {
            return false;
        }
        $this->transition($booking, $to, $attributes, $note, $context, $actorType);

        return true;
    }
}
