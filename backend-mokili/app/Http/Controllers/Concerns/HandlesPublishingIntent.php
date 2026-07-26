<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Shared across every Partner listing controller (Logement, Voiture,
 * Divertissement, Marketplace, Fret): the Form.vue submit buttons send
 * an `intent` field ('draft' or 'submit') alongside the listing data,
 * which this maps to the moderation `status` column (see
 * App\Models\Concerns\HasPublishingStatus). Editing an already
 * published/pending listing without picking an intent (e.g. a partner
 * just fixing a typo) leaves its current status untouched instead of
 * silently reverting it to draft.
 */
trait HandlesPublishingIntent
{
    protected function resolveStatus(Request $request, ?string $currentStatus = null): string
    {
        return match ($request->string('intent')->value()) {
            'submit' => 'pending',
            'draft' => 'draft',
            default => $currentStatus ?? 'draft',
        };
    }
}
