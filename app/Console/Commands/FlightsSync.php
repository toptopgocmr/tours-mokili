<?php

namespace App\Console\Commands;

use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Services\Flights\FlightAudit;
use App\Services\Flights\FlightFulfillmentService;
use App\Services\Flights\Suppliers\FlightSupplierManager;
use App\Services\Flights\Suppliers\SyncsBooking;
use App\Services\Flights\Tracking\FlightTrackingService;
use Illuminate\Console\Command;
use Throwable;

class FlightsSync extends Command
{
    protected $signature = 'flights:sync';

    protected $description = 'Synchronise les reservations avec les fournisseurs (billets emis, annulations) et suit les vols (retards, annulations).';

    public function handle(FlightSupplierManager $suppliers, FlightFulfillmentService $fulfillment, FlightTrackingService $tracking): int
    {
        $orders = FlightOrder::query()
            ->with(['supplier', 'offer', 'passengers'])
            ->whereIn('status', [FlightStatus::BOOKED, FlightStatus::TICKETING_PENDING, FlightStatus::TICKETED, FlightStatus::FAILED])
            ->whereNotNull('supplier_order_id')
            ->where('updated_at', '>', now()->subDays(60))
            ->get();

        $attached = $lost = $tracked = 0;

        foreach ($orders as $order) {
            try {
                $driver = $suppliers->driver($order->supplier);

                if ($driver instanceof SyncsBooking) {
                    $remote = $driver->remoteState($order);

                    if ($remote === null) {
                        if ($order->status !== FlightStatus::FAILED || ! $order->is_anomaly) {
                            $order->update(['is_anomaly' => true, 'failure_reason' => 'Reservation introuvable ou annulee chez le fournisseur (PNR '.$order->pnr.').']);
                            FlightAudit::log('sync.booking_missing', $order, [], 'system');
                            $lost++;
                        }
                        continue;
                    }

                    // Billets apparus apres coup (emission par le consolidateur) : rattachement via le moteur,
                    // qui relit toujours l'etat reel avant d'agir.
                    if ($order->status === FlightStatus::FAILED && $order->pnr && count($remote['tickets']) === $order->passengers->count()) {
                        $fulfillment->retry($order, 'system:sync');
                        $attached++;
                    }
                }

                $tracked += $tracking->refresh($order->fresh(['offer']));
            } catch (Throwable $e) {
                report($e);
                $this->warn($order->reference.' : '.$e->getMessage());
            }
        }

        $this->info("Dossiers: {$orders->count()} | billets rattaches: {$attached} | introuvables: {$lost} | segments suivis: {$tracked}");

        return self::SUCCESS;
    }
}
