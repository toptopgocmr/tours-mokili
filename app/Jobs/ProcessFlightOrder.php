<?php

namespace App\Jobs;

use App\Models\Flights\FlightOrder;
use App\Services\Flights\FlightFulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Reservation + emission d'un dossier de vol dont le paiement est confirme. */
class ProcessFlightOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Les reprises sont gerees par le moteur (statuts + cles d'idempotence), pas par la file.
    public int $tries = 1;

    public function __construct(public int $orderId) {}

    public function handle(FlightFulfillmentService $fulfillment): void
    {
        if ($order = FlightOrder::find($this->orderId)) {
            $fulfillment->process($order);
        }
    }
}
