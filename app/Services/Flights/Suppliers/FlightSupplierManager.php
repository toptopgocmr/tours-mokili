<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightSupplier;
use InvalidArgumentException;

/** Instancie le connecteur d'un fournisseur (architecture modulaire, section 17). */
class FlightSupplierManager
{
    public function driver(FlightSupplier $supplier): FlightSupplierDriver
    {
        $class = config("flights.drivers.{$supplier->driver}");

        if (! $class) {
            throw new InvalidArgumentException("Connecteur de vol inconnu : {$supplier->driver}");
        }

        return app($class);
    }

    /** @return \Illuminate\Support\Collection<int, FlightSupplier> */
    public function active()
    {
        return FlightSupplier::query()->where('is_active', true)->get();
    }
}
