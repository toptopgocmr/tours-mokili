<?php

namespace App\Services\Flights\Tracking;

use Illuminate\Support\Facades\Http;
use Throwable;

/** AviationStack : statut en temps reel (scheduled, active, landed, cancelled, incident, diverted) + retards. */
class AviationStackProvider implements TrackingProvider
{
    public function name(): string
    {
        return 'aviationstack';
    }

    public function enabled(): bool
    {
        return filled(config('flights.tracking.aviationstack.key'));
    }

    public function status(string $carrier, string $number, string $date): ?array
    {
        try {
            $res = Http::timeout(15)->acceptJson()->get(rtrim(config('flights.tracking.aviationstack.base_url'), '/').'/flights', [
                'access_key' => config('flights.tracking.aviationstack.key'),
                'flight_iata' => $carrier.$number,
                'flight_date' => $date,
            ]);
        } catch (Throwable $e) {
            return null;
        }

        $f = $res->successful() ? ($res->json('data.0') ?? null) : null;
        if (! $f) {
            return null;
        }

        $delay = max((int) ($f['departure']['delay'] ?? 0), (int) ($f['arrival']['delay'] ?? 0));
        $status = $f['flight_status'] ?? 'unknown';
        if ($status === 'scheduled' && $delay >= 15) {
            $status = 'delayed';
        }
        if ($status === 'incident') {
            $status = 'diverted';
        }

        return [
            'status' => $status,
            'delay_minutes' => $delay,
            'estimated_departure' => $f['departure']['estimated'] ?? null,
            'estimated_arrival' => $f['arrival']['estimated'] ?? null,
        ];
    }
}
