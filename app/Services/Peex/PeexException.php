<?php

namespace App\Services\Peex;

use Exception;

/**
 * Thrown whenever the Peex API returns an error payload
 * (see https://peex-api-docs.peexit.com/resources#response-format).
 */
class PeexException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $errorName = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
