<?php

namespace App\Services\Flights;

use RuntimeException;

/** Erreur metier affichable au client (offre expiree, prix modifie...). */
class FlightException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'error', public readonly array $data = [])
    {
        parent::__construct($message);
    }
}
