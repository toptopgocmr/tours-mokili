<?php

namespace App\Services\Flights\Tracking;

use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightSegmentStatus;
use App\Models\Flights\FlightStatus;
use App\Services\Flights\FlightAudit;
use App\Services\Flights\FlightNotifier;
use Carbon\Carbon;

/**
 * Suit les segments des dossiers reserves/emis (fenetre autour du depart),
 * stocke le dernier statut et previent le client en cas de retard important,
 * d'annulation ou de deroutement.
 */
class FlightTrackingService
{
    public function __construct(private FlightNotifier $notifier) {}

    /** @return TrackingProvider[] */
    private function providers(): array
    {
        $map = [
            'amadeus' => app(AmadeusTrackingProvider::class),
            'aviationstack' => app(AviationStackProvider::class),
        ];

        return collect(config('flights.tracking.providers', []))
            ->map(fn ($name) => $map[trim($name)] ?? null)
            ->filter(fn ($p) => $p && $p->enabled())
            ->values()
            ->all();
    }

    public function refresh(FlightOrder $order): int
    {
        if (! in_array($order->status, [FlightStatus::BOOKED, FlightStatus::TICKETING_PENDING, FlightStatus::TICKETED], true)) {
            return 0;
        }

        $providers = $this->providers();
        if (! $providers) {
            return 0;
        }

        $window = (int) config('flights.tracking.window_hours', 72);
        $updated = 0;

        $itinerary = $order->offer->itinerary;
        $legs = $itinerary['legs'] ?? [$itinerary['outbound'] ?? [], $itinerary['inbound'] ?? []];

        foreach ($legs as $leg) {
            foreach ($leg as $seg) {
                $departure = Carbon::parse($seg['departure']);
                if ($departure->lt(now()->subHours(12)) || $departure->gt(now()->addHours($window))) {
                    continue;
                }

                $carrier = substr($seg['flight_number'], 0, 2);
                $number = substr($seg['flight_number'], 2);
                $date = $departure->format('Y-m-d');

                foreach ($providers as $provider) {
                    if ($info = $provider->status($carrier, $number, $date)) {
                        $this->store($order, $seg, $date, $info, $provider->name());
                        $updated++;
                        break;
                    }
                }
            }
        }

        return $updated;
    }

    private function store(FlightOrder $order, array $seg, string $date, array $info, string $provider): void
    {
        $row = FlightSegmentStatus::firstOrNew([
            'flight_order_id' => $order->id, 'flight_number' => $seg['flight_number'], 'flight_date' => $date,
        ]);

        $row->fill($info + []);
        $row->fill([
            'origin' => $seg['from'], 'destination' => $seg['to'], 'provider' => $provider, 'checked_at' => now(),
        ]);

        $alert = (int) config('flights.tracking.delay_alert_minutes', 30);
        $bad = in_array($info['status'], ['cancelled', 'diverted'], true);
        $newDelay = $info['delay_minutes'] >= $alert && $info['delay_minutes'] > (int) $row->notified_delay + 14;

        if ($bad && $row->notified_status !== $info['status']) {
            $row->notified_status = $info['status'];
            $order->update(['is_anomaly' => true, 'failure_reason' => 'Vol '.$seg['flight_number'].' : '.$info['status'].' (suivi).']);
            FlightAudit::log('tracking.disruption', $order, ['flight' => $seg['flight_number'], 'status' => $info['status']], 'system');
            $this->notifier->notify($order, 'flight_cancelled');
        } elseif (! $bad && $newDelay) {
            $row->notified_delay = $info['delay_minutes'];
            FlightAudit::log('tracking.delay', $order, ['flight' => $seg['flight_number'], 'delay' => $info['delay_minutes']], 'system');
            $this->notifier->notify($order, 'flight_delayed');
        }

        $row->save();
    }
}
