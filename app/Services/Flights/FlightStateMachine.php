<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Models\Flights\FlightStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * Machine a etats explicite (cahier des charges, section 11). SEUL point
 * d'entree pour changer le statut d'un dossier : transitions controlees
 * cote serveur et historisees. Rejouer la meme transition est sans effet
 * (webhooks rejoues, double clic...).
 */
class FlightStateMachine
{
    public function transition(FlightOrder $order, string $to, string $actor = 'system', ?string $reason = null): FlightOrder
    {
        return DB::transaction(function () use ($order, $to, $actor, $reason) {
            $locked = FlightOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->status;

            if ($from === $to) {
                return $locked;
            }

            if (! FlightStatus::canTransition($from, $to)) {
                throw new InvalidFlightTransition("Transition interdite : {$from} -> {$to}.");
            }

            $updates = ['status' => $to];

            match ($to) {
                FlightStatus::PAYMENT_CONFIRMED => $updates['payment_confirmed_at'] = now(),
                FlightStatus::BOOKED => $updates['booked_at'] = now(),
                FlightStatus::TICKETED => $updates['ticketed_at'] = now(),
                default => null,
            };

            $locked->update($updates);

            FlightStatusHistory::create([
                'flight_order_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor' => $actor,
                'reason' => $reason,
            ]);

            FlightAudit::log('status.changed', $locked, ['from' => $from, 'to' => $to, 'reason' => $reason], $actor);

            // Garde l'instance de l'appelant a jour.
            $order->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }
}
