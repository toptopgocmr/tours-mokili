<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voiture\Vehicle;
use App\Models\Voyage\TravelOffer;
use App\Services\Partner\PartnerCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace partenaire - RESERVATIONS : billets vendus sur les trajets et
 * offres du partenaire (et reservations de ses vehicules), avec client,
 * places, montant et statut du paiement. Chiffre d'affaires du mois.
 */
class BookingController extends Controller
{
    public const PAID = ['confirmed', 'paid', 'completed'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $base = self::ownedBookings(app(PartnerCatalog::class)->ownedIds($user));

        $status = $request->query('statut');
        $type = array_key_exists((string) $request->query('rubrique'), PartnerCatalog::TYPES) ? $request->query('rubrique') : null;
        $bookings = (clone $base)
            ->with(['user:id,name,phone,email', 'bookable'])
            ->when($type, fn ($q) => $q->where('bookable_type', PartnerCatalog::TYPES[$type][0]))
            ->when($status === 'payees', fn ($q) => $q->whereIn('status', self::PAID))
            ->when($status === 'en_attente', fn ($q) => $q->where('status', 'awaiting_payment'))
            ->when($status === 'annulees', fn ($q) => $q->whereIn('status', ['cancelled', 'refunded', 'failed']))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'id' => $b->id,
                'reference' => $b->reference,
                'created_at' => $b->created_at?->toIso8601String(),
                'status' => $b->status,
                'quantity' => $b->quantity,
                'total' => (float) $b->total_amount,
                'currency' => $b->currency,
                'notes' => $b->notes,
                'client' => $b->user ? ['name' => $b->user->name, 'phone' => $b->user->phone, 'email' => $b->user->email] : null,
                'item' => self::describe($b),
                // Location : identite et permis du conducteur.
                'driver' => isset($b->meta['driver']) ? [
                    'id' => ($b->meta['driver']['id_type_label'] ?? '').' n° '.($b->meta['driver']['id_number'] ?? ''),
                    'license' => $b->meta['driver']['license_number'] ? 'Permis n° '.$b->meta['driver']['license_number'].' (obtenu le '.\Carbon\Carbon::parse($b->meta['driver']['license_date'])->format('d/m/Y').')' : 'Avec chauffeur',
                    'id_url' => route('rental.document', [$b->id, 'id_document']),
                    'license_url' => ! empty($b->meta['driver']['license_document']) ? route('rental.document', [$b->id, 'license_document']) : null,
                ] : null,
            ]);

        $paid = (clone $base)->whereIn('status', self::PAID);
        $present = collect(PartnerCatalog::TYPES)->filter(fn ($t) => (clone $base)->where('bookable_type', $t[0])->exists())
            ->map(fn ($t) => $t[2]);

        return Inertia::render('Partner/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => ['statut' => $status, 'rubrique' => $type],
            'rubriques' => $present,
            'stats' => [
                'month_revenue' => (float) (clone $paid)->where('created_at', '>=', now()->startOfMonth())->sum('total_amount'),
                'month_seats' => (int) (clone $paid)->where('created_at', '>=', now()->startOfMonth())->sum('quantity'),
                'awaiting' => (clone $base)->where('status', 'awaiting_payment')->count(),
                'total_paid' => (clone $paid)->count(),
            ],
        ]);
    }

    /** Reservations portant sur les offres du partenaire (toutes rubriques). */
    public static function ownedBookings(array $ownedIds)
    {
        return Booking::query()->where(function ($q) use ($ownedIds) {
            $any = false;
            foreach ($ownedIds as $model => $ids) {
                if ($ids) {
                    $q->orWhere(fn ($w) => $w->where('bookable_type', $model)->whereIn('bookable_id', $ids));
                    $any = true;
                }
            }
            if (! $any) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /** Ce qui a ete reserve : trajet, nuitees, location, billet, entree... */
    public static function describe(Booking $b): ?array
    {
        $m = $b->bookable;
        $from = $b->starts_at?->format('Y-m-d\TH:i');

        return match (true) {
            $m instanceof TravelOffer => ['kind' => $m->type === 'vol' ? 'Vol' : 'Trajet', 'title' => trim(($m->origin_city ?? '').' → '.$m->destination_city, ' →'),
                'mode' => $m->transport_mode_label, 'departure_at' => $m->departure_at?->format('Y-m-d\TH:i'), 'unit' => 'place(s)'],
            $m instanceof \App\Models\Logement\LodgingListing => ['kind' => 'Hebergement', 'title' => $m->title, 'mode' => trim(($m->property_type_label ?? '').' · '.$m->city, ' ·'),
                'departure_at' => $from, 'until' => $b->ends_at?->format('Y-m-d'), 'unit' => 'nuit(s)'],
            $m instanceof Vehicle => ['kind' => $m->offer_type === 'mecanique' ? 'Garage' : 'Voiture', 'title' => $m->title, 'mode' => $m->offer_type_label ?? null,
                'departure_at' => $from, 'until' => $b->ends_at?->format('Y-m-d'), 'unit' => $m->offer_type === 'location' ? 'jour(s)' : ''],
            $m instanceof \App\Models\Divertissement\Event => ['kind' => 'Evenement', 'title' => $m->title, 'mode' => $m->venue, 'departure_at' => $m->starts_at?->format('Y-m-d\TH:i'), 'unit' => 'billet(s)'],
            $m instanceof \App\Models\Loisirs\AttractionPark => ['kind' => 'Parc', 'title' => $m->name, 'mode' => $m->city, 'departure_at' => $from,
                'unit' => isset($b->meta['adults']) ? $b->meta['adults'].' adulte(s), '.($b->meta['children'] ?? 0).' enfant(s)' : 'billet(s)'],
            $m !== null => ['kind' => class_basename($m), 'title' => $m->title ?? $m->name ?? '', 'mode' => null, 'departure_at' => $from, 'unit' => ''],
            default => null,
        };
    }
}
