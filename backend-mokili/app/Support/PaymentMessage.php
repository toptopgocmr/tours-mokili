<?php

namespace App\Support;

/**
 * Messages de paiement montres au client : on retire le nom des agregateurs
 * (Peex, pawaPay, MaishaPay, Digitwace), qui restent visibles en back-office.
 */
class PaymentMessage
{
    public static function clean(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }
        $m = preg_replace('/\s*\b(maishapay|pawapay|peex|digitwace)\b/i', '', $message);
        $m = preg_replace('/\.{2,}/', '.', trim(preg_replace('/\s{2,}/', ' ', $m)));

        return $m === '' ? 'Le paiement a echoue.' : $m;
    }
}
