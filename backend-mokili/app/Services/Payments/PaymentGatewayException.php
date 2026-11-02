<?php

namespace App\Services\Payments;

use RuntimeException;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(string $message, public readonly array $raw = [], int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
