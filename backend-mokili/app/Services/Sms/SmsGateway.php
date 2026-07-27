<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * Sends a short text message to $phone. Implementations should throw
     * on failure so PhoneOtpController can surface a clear error instead
     * of silently pretending a code was sent.
     */
    public function send(string $phone, string $message): void;
}
