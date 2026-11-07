<?php

namespace App\Services\Flights;

use RuntimeException;

/** Le prix a change a la revalidation : le client doit confirmer le nouveau prix. */
class FlightPriceChanged extends RuntimeException
{
    public function __construct(public readonly float $newPrice, public readonly string $currency)
    {
        parent::__construct('Le prix de cette offre a change.');
    }
}
