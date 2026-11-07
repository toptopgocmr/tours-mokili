<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Notification applicative (toujours) + e-mail (au mieux). Un echec d'envoi ne bloque jamais un traitement. */
class FreightNotifier
{
    public function notify(?User $user, string $event, string $ref, string $message): void
    {
        if (! $user) {
            return;
        }

        $text = 'MOKILI TOUR Fret - '.$message;
        FreightNotification::create(['user_id' => $user->id, 'event' => $event, 'subject_ref' => $ref, 'message' => $text, 'channel' => 'app']);

        if ($user->email) {
            try {
                Mail::raw($text, fn ($m) => $m->to($user->email)->subject('MOKILI TOUR Fret - '.$ref));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
