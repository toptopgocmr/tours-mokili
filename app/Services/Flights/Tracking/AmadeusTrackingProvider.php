<?php

namespace App\Services\Flights\Tracking;

use App\Services\Flights\Suppliers\AmadeusClient;
use Carbon\Carbon;
use Throwable;

/** Amadeus On-Demand Flight Status (horaires programmes / estimes). */
class AmadeusTrackingProvider implements TrackingProvider
{
    public function __construct(private AmadeusClient $client) {}

    public function name(): string
    {
        return 'amadeus';
    }

    public function enabled(): bool
    {
        return $this->client->configured();
    }

    public function status(string $carrier, string $number, string $date): ?array
    {
        try {
            $res = $this->client->request('GET', 'v2/schedule/flights', ['query' => [
                'carrierCode' => $carrier, 'flightNumber' => $number, 'scheduledDepartureDate' => $date,
            ]]);
        } catch (Throwable $e) {
            return null;
        }

        $points = $res['data'][0]['flightPoints'] ?? [];
        if (! $points) {
            return null;
        }

        $dep = $this->timings($points[0]['departure']['timings'] ?? []);
        $arr = $this->timings(end($points)['arrival']['timings'] ?? []);

        $delay = 0;
        $est = $dep['ETD'] ?? $dep['ATD'] ?? null;
        if ($est && isset($dep['STD'])) {
            $delay = (int) Carbon::parse($dep['STD'])->diffInMinutes(Carbon::parse($est), false);
        }

        return [
            'status' => isset($dep['ATD']) ? 'active' : ($delay >= 15 ? 'delayed' : 'scheduled'),
            'delay_minutes' => max(0, $delay),
            'estimated_departure' => $est,
            'estimated_arrival' => $arr['ETA'] ?? $arr['STA'] ?? null,
        ];
    }

    private function timings(array $timings): array
    {
        $out = [];
        foreach ($timings as $t) {
            $out[$t['qualifier']] = $t['value'];
        }

        return $out;
    }
}
