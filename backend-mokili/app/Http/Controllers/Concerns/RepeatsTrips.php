<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Voyage\TravelOffer;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Trajets reguliers (bus, train, canot, taxi) : recree le trajet aux jours de
 * la semaine choisis jusqu'a la date indiquee, aux memes heures. Utilise par
 * l'admin et par l'espace partenaire.
 */
trait RepeatsTrips
{
    protected function repeatTrip(Request $request, TravelOffer $first): int
    {
        if ($first->type !== 'circuit' || ! $first->departure_at || ! $request->filled('repeat_until')) {
            return 0;
        }
        $rules = $request->validate([
            'repeat_until' => ['date', 'after:departure_at', 'before:+181 days'],
            'repeat_days' => ['required', 'array', 'min:1'],
            'repeat_days.*' => ['integer', 'between:1,7'],
        ]);
        $days = array_map('intval', $rules['repeat_days']);
        $until = Carbon::parse($rules['repeat_until'])->endOfDay();
        $count = 0;
        for ($d = $first->departure_at->copy()->addDay(); $d->lte($until) && $count < 180; $d->addDay()) {
            if (! in_array($d->isoWeekday(), $days, true)) {
                continue;
            }
            $offset = (int) $first->departure_at->copy()->startOfDay()->diffInDays($d->copy()->startOfDay());
            $copy = $first->replicate(['slug']);
            $copy->departure_at = $first->departure_at->copy()->addDays($offset);
            $copy->arrival_at = $first->arrival_at?->copy()->addDays($offset);
            $copy->save();
            $count++;
        }

        return $count;
    }
}
