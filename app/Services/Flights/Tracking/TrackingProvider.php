<?php

namespace App\Services\Flights\Tracking;

interface TrackingProvider
{
    public function name(): string;

    public function enabled(): bool;

    /**
     * @return array{status:string, delay_minutes:int, estimated_departure:?string, estimated_arrival:?string}|null null = vol inconnu du fournisseur
     */
    public function status(string $carrier, string $number, string $date): ?array;
}
