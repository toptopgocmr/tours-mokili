<?php

namespace App\Services\Fret;

use App\Models\Fret\FreightDocument;
use App\Models\Fret\FreightOrder;
use App\Models\Fret\Shipment;
use App\Models\Fret\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

/** Suivi par evenements (statut, lieu, date, source, commentaire, preuve). Ajout seul ; correction tracee. */
class FreightTrackingService
{
    /** Statuts de transport proposes au partenaire (cadrage section 8). */
    public const STATUSES = [
        'PRISE_EN_CHARGE' => 'Prise en charge',
        'EN_TRANSIT' => 'En transit',
        'ARRIVE_A_DESTINATION' => 'Arrive a destination',
        'EN_DEDOUANEMENT' => 'En dedouanement',
        'EN_LIVRAISON' => 'En livraison',
        'LIVRE' => 'Livre',
        'RETARD' => 'Retard declare',
        'DOCUMENT_REQUIS' => 'Document demande',
    ];

    /** Statuts qui font avancer l'etat du colis (RETARD / DOCUMENT_REQUIS sont informatifs). */
    private const SHIPMENT_STATUS = [
        'PRISE_EN_CHARGE' => 'prise_en_charge', 'EN_TRANSIT' => 'en_transit', 'ARRIVE_A_DESTINATION' => 'arrive',
        'EN_DEDOUANEMENT' => 'dedouanement', 'EN_LIVRAISON' => 'en_livraison', 'LIVRE' => 'livre',
    ];

    public function __construct(private FreightNotifier $notifier) {}

    public function add(Shipment $shipment, string $status, ?string $location, ?string $comment, string $source, ?Carbon $at = null, ?FreightDocument $proof = null, ?int $corrects = null): TrackingEvent
    {
        if (! array_key_exists($status, self::STATUSES)) {
            throw new RuntimeException('Statut de suivi inconnu.');
        }

        $order = $shipment->freight_order_id ? FreightOrder::find($shipment->freight_order_id) : null;
        // Pas d'expedition sans reservation confirmee (paiement et prise en charge restent distincts).
        if ($order && ! in_array($order->status, [FreightOrder::CONFIRMED, FreightOrder::DISPUTE], true)) {
            throw new RuntimeException('Le suivi ne peut demarrer qu\'apres la confirmation de la reservation.');
        }
        if ($status === 'LIVRE' && ! $proof && ! $comment) {
            throw new RuntimeException('Precisez un commentaire ou joignez une preuve de livraison.');
        }

        $event = TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $status,
            'location' => $location,
            'occurred_at' => $at ?? now(),
            'comment' => $comment,
            'source' => $source,
            'document_id' => $proof?->id,
            'corrects_event_id' => $corrects,
        ]);

        if ($mapped = self::SHIPMENT_STATUS[$status] ?? null) {
            $update = ['status' => $mapped];
            if ($status === 'PRISE_EN_CHARGE') {
                $update['picked_up_at'] = $event->occurred_at;
            }
            if ($status === 'LIVRE') {
                $update += ['delivered_at' => $event->occurred_at, 'proof_at' => $proof ? now() : null];
            }
            $shipment->update($update);
        }

        FreightAudit::log('tracking.event', $shipment, $source, $comment, ['status' => $status, 'location' => $location, 'corrects' => $corrects]);

        $label = self::STATUSES[$status];
        $this->notifier->notify($shipment->user, 'tracking_'.strtolower($status), $shipment->tracking_code,
            "Expedition {$shipment->tracking_code} : {$label}".($location ? " ({$location})" : '').'.');

        return $event;
    }

    /** Une erreur ne s'efface pas : un nouvel evenement la corrige. */
    public function correct(Shipment $shipment, TrackingEvent $wrong, string $status, ?string $location, string $comment, string $source): TrackingEvent
    {
        abort_unless($wrong->shipment_id === $shipment->id, 404);

        return $this->add($shipment, $status, $location, 'Correction : '.$comment, $source, null, null, $wrong->id);
    }

    public static function actorFor(User $user): string
    {
        return ($user->isStaff() ? 'admin:' : 'partner:').$user->id;
    }
}
