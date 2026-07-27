<?php

namespace App\Models\Concerns;

/**
 * Shared moderation workflow for every partner-ownable listing
 * (Logement, Voiture, Divertissement, Marketplace, Fret - Voyage is
 * excluded, it's centrally managed by admin/agent staff).
 *
 * `status` moves draft -> pending -> published/rejected. A partner
 * chooses draft (not ready) or pending (submit for review) when saving;
 * only admin/agent staff can move something to published or rejected
 * (see Admin\ModerationController). `is_active` remains the partner's
 * own show/hide switch and stays independent of this workflow - both
 * conditions must hold for something to be publicly visible.
 */
trait HasPublishingStatus
{
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'published');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending');
    }

    public function isEditableAsDraft(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }
}
