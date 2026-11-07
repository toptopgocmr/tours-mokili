<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightOrder;

/** Connecteur capable de relire l'etat reel d'une reservation chez le fournisseur. */
interface SyncsBooking
{
    /**
     * @return array{tickets: array<int,string>}|null null = reservation introuvable / annulee chez le fournisseur
     */
    public function remoteState(FlightOrder $order): ?array;
}
