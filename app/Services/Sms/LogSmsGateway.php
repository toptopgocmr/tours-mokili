<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Default SMS "gateway": just writes the message to the Laravel log
 * instead of sending a real text. This is what SMS_GATEWAY=log (the
 * default, see config/services.php) resolves to, so phone/OTP sign-in is
 * wired end-to-end and testable (read the code from the log / Railway
 * deploy logs) before a real provider is plugged in.
 *
 * To go live: implement SmsGateway for whichever provider covers your
 * users' countries (Twilio, Africa's Talking, Orange SMS API, etc. are
 * common choices for CEMAC/DRC numbers), then bind it in
 * AppServiceProvider based on config('services.sms.gateway').
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info("[SMS -> $phone] $message");
    }
}
