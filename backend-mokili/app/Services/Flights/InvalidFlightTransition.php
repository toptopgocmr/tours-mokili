<?php

namespace App\Services\Flights;

use RuntimeException;

class InvalidFlightTransition extends RuntimeException
{
    public function __construct(public readonly string $from, public readonly string $to)
    {
        parent::__construct("Transition interdite : {$from} -> {$to}");
    }
}
