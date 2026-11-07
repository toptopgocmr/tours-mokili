<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightOffer;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\User;
use RuntimeException;

/** Validation des partenaires : approuver, refuser, demander une correction, suspendre, reactiver (motif + historique). */
class FreightPartnerService
{
    public function __construct(private FreightNotifier $notifier) {}

    public function decide(User $staff, FreightPartnerProfile $profile, string $decision, ?string $reason): FreightPartnerProfile
    {
        $map = ['approve' => 'approved', 'reject' => 'rejected', 'correction' => 'needs_correction', 'suspend' => 'suspended', 'reactivate' => 'approved'];
        if (! isset($map[$decision])) {
            throw new RuntimeException('Decision inconnue.');
        }
        if ($decision !== 'approve' && $decision !== 'reactivate' && blank($reason)) {
            throw new RuntimeException('Un motif est obligatoire.');
        }

        $from = $profile->status;
        $profile->update(['status' => $map[$decision], 'review_note' => $reason, 'reviewed_by' => $staff->id, 'reviewed_at' => now()]);

        // Une suspension retire immediatement les offres du site (le scope active() exige un partenaire approuve).
        FreightAudit::log('partner.'.$decision, $profile, 'admin:'.$staff->id, $reason, ['from' => $from, 'to' => $map[$decision]]);

        $messages = [
            'approve' => 'Votre candidature est approuvee : vous pouvez publier vos offres.',
            'reactivate' => 'Votre compte partenaire fret est reactive.',
            'reject' => 'Votre candidature a ete refusee. Motif : '.$reason,
            'correction' => 'Votre candidature necessite une correction : '.$reason,
            'suspend' => 'Votre compte partenaire fret est suspendu. Motif : '.$reason,
        ];
        $this->notifier->notify($profile->user, 'partner_'.$decision, $profile->company_name, $messages[$decision]);

        return $profile;
    }

    /** Soumission / resoumission de la candidature par le partenaire. */
    public function submit(User $user, array $data): FreightPartnerProfile
    {
        $profile = FreightPartnerProfile::firstOrNew(['user_id' => $user->id]);

        if ($profile->exists && in_array($profile->status, ['approved', 'suspended'], true)) {
            // Un partenaire approuve peut modifier ses informations non sensibles ; le statut ne change pas.
            $profile->fill($data)->save();
            FreightAudit::log('partner.updated', $profile, 'user:'.$user->id);

            return $profile;
        }

        $profile->fill($data);
        $profile->status = FreightPartnerProfile::PENDING;
        $profile->save();
        FreightAudit::log('partner.submitted', $profile, 'user:'.$user->id);

        return $profile;
    }

    /** Un partenaire dont les offres sensibles ont change repart en validation. */
    public function resubmitIfSensitive(FreightOffer $offer, array $changed): void
    {
        if ($offer->status === 'published' && array_intersect(array_keys($changed), FreightOffer::SENSITIVE)) {
            $offer->update(['status' => 'pending']);
            FreightAudit::log('offer.resubmitted', $offer, 'user:'.$offer->carrier_id, 'Modification sensible', ['fields' => array_keys($changed)]);
        }
    }
}
