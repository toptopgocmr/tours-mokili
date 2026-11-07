<?php

namespace App\Services\Fret;

use App\Models\Fret\Claim;
use App\Models\Fret\ClaimMessage;
use App\Models\Fret\FreightOrder;
use App\Models\User;
use RuntimeException;

class FreightClaimService
{
    public function __construct(private FreightNotifier $notifier) {}

    public function open(User $user, FreightOrder $order, string $reason, string $description): Claim
    {
        abort_unless($order->user_id === $user->id, 403);
        if (! $order->payment_confirmed_at) {
            throw new RuntimeException('Une reclamation concerne une commande payee.');
        }

        $claim = Claim::create(['freight_order_id' => $order->id, 'user_id' => $user->id, 'reason' => $reason, 'description' => $description]);
        ClaimMessage::create(['claim_id' => $claim->id, 'user_id' => $user->id, 'author_role' => 'client', 'body' => $description]);

        if ($order->canTransition(FreightOrder::DISPUTE)) {
            app(FreightOrderService::class)->transition($order, FreightOrder::DISPUTE, 'user:'.$user->id, 'Reclamation '.$claim->reference);
        }

        FreightAudit::log('claim.opened', $claim, 'user:'.$user->id, $reason);
        $this->notifier->notify($user, 'claim_opened', $claim->reference, "Votre reclamation {$claim->reference} est ouverte.");
        $this->notifier->notify($order->partner, 'claim_opened', $claim->reference, "Reclamation {$claim->reference} sur la commande {$order->reference} : merci de repondre.");

        return $claim;
    }

    public function message(User $author, Claim $claim, string $body): ClaimMessage
    {
        $role = $author->isStaff() ? 'staff' : ($author->id === $claim->user_id ? 'client' : 'partner');
        abort_unless($role !== 'partner' || $claim->order->partner_user_id === $author->id, 403);
        abort_unless($role !== 'client' || $claim->user_id === $author->id, 403);

        $message = ClaimMessage::create(['claim_id' => $claim->id, 'user_id' => $author->id, 'author_role' => $role, 'body' => $body]);

        if ($role === 'staff' && $claim->status === 'open') {
            $claim->update(['status' => 'in_review']);
        }
        foreach ([$claim->user, $claim->order->partner] as $target) {
            if ($target && $target->id !== $author->id) {
                $this->notifier->notify($target, 'claim_message', $claim->reference, "Nouvelle reponse sur la reclamation {$claim->reference}.");
            }
        }

        return $message;
    }

    /** Decision documentee par le staff (jamais effacee). */
    public function resolve(User $staff, Claim $claim, string $status, string $resolution): Claim
    {
        abort_unless(in_array($status, ['in_review', 'documents_requested', 'resolved', 'rejected'], true), 422);

        $claim->update([
            'status' => $status,
            'resolution' => $resolution,
            'resolved_by' => in_array($status, ['resolved', 'rejected'], true) ? $staff->id : null,
            'resolved_at' => in_array($status, ['resolved', 'rejected'], true) ? now() : null,
        ]);
        ClaimMessage::create(['claim_id' => $claim->id, 'user_id' => $staff->id, 'author_role' => 'staff', 'body' => $resolution]);

        $order = $claim->order;
        if (in_array($status, ['resolved', 'rejected'], true) && $order->status === FreightOrder::DISPUTE
            && ! $order->claims()->whereIn('status', ['open', 'in_review', 'documents_requested'])->exists()) {
            app(FreightOrderService::class)->transition($order, FreightOrder::CONFIRMED, 'admin:'.$staff->id, 'Reclamations closes');
        }

        FreightAudit::log('claim.'.$status, $claim, 'admin:'.$staff->id, $resolution);
        $this->notifier->notify($claim->user, 'claim_update', $claim->reference, "Reclamation {$claim->reference} : {$status}.");

        return $claim->fresh();
    }
}
