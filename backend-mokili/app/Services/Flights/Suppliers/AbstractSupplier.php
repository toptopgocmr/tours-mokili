<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightSupplier as SupplierModel;
use App\Services\Flights\Contracts\FlightSupplier;
use App\Services\Flights\Data\SearchCriteria;
use Illuminate\Http\Request;

abstract class AbstractSupplier implements FlightSupplier
{
    protected ?SupplierModel $supplier = null;

    public function setSupplier(SupplierModel $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function parseWebhook(Request $request): ?array
    {
        return null;
    }

    /** Montant par type de passager -> lignes + total. */
    protected function passengerLines(SearchCriteria $c, float $adult, float $child, float $infant): array
    {
        $lines = [['type' => 'ADT', 'count' => $c->adults, 'unit_amount' => round($adult, 2)]];
        if ($c->children) {
            $lines[] = ['type' => 'CHD', 'count' => $c->children, 'unit_amount' => round($child, 2)];
        }
        if ($c->infants) {
            $lines[] = ['type' => 'INF', 'count' => $c->infants, 'unit_amount' => round($infant, 2)];
        }

        return $lines;
    }

    protected function linesTotal(array $lines): float
    {
        return round(array_sum(array_map(fn ($l) => $l['count'] * $l['unit_amount'], $lines)), 2);
    }
}
