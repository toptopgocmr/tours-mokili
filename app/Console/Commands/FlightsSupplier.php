<?php

namespace App\Console\Commands;

use App\Models\Flights\FlightSupplier;
use App\Services\Flights\Suppliers\AmadeusClient;
use Illuminate\Console\Command;

class FlightsSupplier extends Command
{
    protected $signature = 'flights:supplier {code : sandbox, amadeus...} {state : on|off}';

    protected $description = 'Active ou desactive un fournisseur de vols (ex: php artisan flights:supplier amadeus on).';

    public function handle(): int
    {
        $supplier = FlightSupplier::where('code', $this->argument('code'))->first();
        if (! $supplier) {
            $this->error('Fournisseur inconnu.');

            return self::FAILURE;
        }

        $on = $this->argument('state') === 'on';

        if ($on && $supplier->driver === 'amadeus' && ! app(AmadeusClient::class)->configured()) {
            $this->error('Definissez AMADEUS_CLIENT_ID et AMADEUS_CLIENT_SECRET avant d\'activer Amadeus.');

            return self::FAILURE;
        }

        $supplier->update(['is_active' => $on]);
        $this->info($supplier->name.' : '.($on ? 'actif' : 'inactif'));

        return self::SUCCESS;
    }
}
