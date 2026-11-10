<?php

namespace App\Services\Voiture;

use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Disponibilite d'un vehicule de location : un vehicule est pris sur une
 * periode s'il a une reservation payee / confirmee qui la chevauche, ou une
 * reservation en attente de paiement creee il y a moins de HOLD_MINUTES
 * (le client est en train de payer). Les jours sont comptes du jour de prise
 * en charge au jour de restitution (restitution le meme jour = 1 jour).
 */
class VehicleAvailability
{
    public const HOLD_MINUTES = 30;

    public static function blocking(Vehicle $vehicle)
    {
        return Booking::query()
            ->where('bookable_type', Vehicle::class)->where('bookable_id', $vehicle->id)
            ->where(fn ($q) => $q->whereIn('status', ['confirmed', 'paid', 'completed'])
                ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '>=', now()->subMinutes(self::HOLD_MINUTES))));
    }

    /** Reservations qui chevauchent [start, end] (jours inclus). */
    public static function conflicts(Vehicle $vehicle, Carbon $start, Carbon $end)
    {
        return self::blocking($vehicle)
            ->where('starts_at', '<=', $end->copy()->endOfDay())
            ->where('ends_at', '>=', $start->copy()->startOfDay())
            ->get(['id', 'starts_at', 'ends_at']);
    }

    public static function isAvailable(Vehicle $vehicle, Carbon $start, Carbon $end): bool
    {
        return self::conflicts($vehicle, $start, $end)->isEmpty();
    }

    public static function days(Carbon $start, Carbon $end): int
    {
        return max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
    }

    /** Jours deja pris sur les N prochains jours (calendrier de la fiche). */
    public static function busyDays(Vehicle $vehicle, int $horizon = 90): array
    {
        $from = now()->startOfDay();
        $to = now()->addDays($horizon)->endOfDay();
        $days = [];
        foreach (self::blocking($vehicle)->where('ends_at', '>=', $from)->where('starts_at', '<=', $to)->get(['starts_at', 'ends_at']) as $b) {
            $s = Carbon::parse($b->starts_at)->max($from);
            $e = Carbon::parse($b->ends_at ?? $b->starts_at)->min($to);
            foreach (CarbonPeriod::create($s->toDateString(), $e->toDateString()) as $d) {
                $days[$d->toDateString()] = true;
            }
        }
        ksort($days);

        return array_keys($days);
    }

    /** Premiere periode libre de meme duree a partir de $start (suggestion). */
    public static function nextFree(Vehicle $vehicle, Carbon $start, int $days): ?string
    {
        $busy = array_flip(self::busyDays($vehicle, 120));
        for ($i = 1; $i <= 90; $i++) {
            $s = $start->copy()->addDays($i);
            $ok = true;
            for ($d = 0; $d <= $days; $d++) {
                if (isset($busy[$s->copy()->addDays($d)->toDateString()])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $s->toDateString();
            }
        }

        return null;
    }
}
