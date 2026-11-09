<?php

namespace App\Services\Flights;

use App\Models\Flights\FlightSupplier as SupplierModel;
use App\Services\Flights\Contracts\FlightSupplier;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FlightSupplierManager
{
    /** Fournisseurs utilisables pour la recherche, par priorite. */
    public function activeSuppliers(): Collection
    {
        return SupplierModel::query()
            ->orderBy('priority')
            ->get()
            ->filter(fn (SupplierModel $s) => $this->isUsable($s))
            ->values();
    }

    public function isUsable(SupplierModel $s): bool
    {
        if (! isset(config('flights.drivers')[$s->driver])) {
            return false;
        }
        if ($s->driver === 'sandbox') {
            return (bool) config('flights.sandbox_enabled');
        }
        if ($s->driver === 'galileo' && ! \App\Services\Flights\Suppliers\GalileoSupplier::configured()) {
            return false; // identifiants Travelport absents : on ne l'interroge pas
        }

        return $s->is_active;
    }

    public function driver(SupplierModel $s): FlightSupplier
    {
        $class = config('flights.drivers')[$s->driver] ?? null;
        if (! $class) {
            throw new InvalidArgumentException("Fournisseur aerien inconnu : {$s->driver}");
        }

        return app($class)->setSupplier($s);
    }
}
