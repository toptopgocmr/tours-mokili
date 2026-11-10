<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voiture\Vehicle;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tableau de bord de l'espace partenaire (role:partner) : selon l'activite
 * (compagnie de bus, train, canots rapides, taxis, compagnie aerienne,
 * agence de billets, garage, hotel...), raccourcis, chiffres du mois,
 * prochains departs et modules ouverts au partenaire.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $catalog = app(\App\Services\Partner\PartnerCatalog::class);
        $owned = $catalog->ownedIds($user);
        $offerIds = $owned[TravelOffer::class];
        $paid = BookingController::ownedBookings($owned)->whereIn('status', BookingController::PAID)
            ->where('created_at', '>=', now()->startOfMonth());
        $promoCount = 0;
        foreach (array_keys(\App\Services\Partner\PartnerCatalog::TYPES) as $t) {
            $promoCount += $catalog->query($t, $user)->where('discount_percent', '>', 0)
                ->where(fn ($w) => $w->whereNull('promo_ends_at')->orWhere('promo_ends_at', '>', now()))->count();
        }

        return Inertia::render('Partner/Dashboard', [
            'company' => [
                'name' => $user->company_name ?: $user->name,
                'activity' => $user->partner_activity,
                'activity_label' => User::PARTNER_ACTIVITIES[$user->partner_activity] ?? null,
                'verified' => $user->isVerifiedPartner(),
            ],
            'counts' => [
                'transport' => $user->travelOffers()->where('type', 'circuit')->where('departure_at', '>=', now())->count(),
                'vols' => $user->travelOffers()->where('type', 'vol')->count(),
                'promotions' => $promoCount,
                'parcs' => count($owned[\App\Models\Loisirs\AttractionPark::class]),
                'location' => $user->vehicles()->whereIn('offer_type', ['location', 'vente'])->count(),
                'garage' => $user->vehicles()->where('offer_type', 'mecanique')->count(),
                'logement' => $user->lodgingListings()->count(),
                'voiture' => $user->vehicles()->count(),
                'divertissement' => $user->events()->count(),
                'marketplace' => $user->products()->count(),
                'fret' => $user->freightOffers()->count(),
            ],
            'month' => [
                'revenue' => (float) (clone $paid)->sum('total_amount'),
                'seats' => (int) (clone $paid)->sum('quantity'),
                'awaiting' => BookingController::ownedBookings($owned)->where('status', 'awaiting_payment')->count(),
            ],
            'nextDepartures' => $user->travelOffers()->where('type', 'circuit')->where('departure_at', '>=', now())
                ->withSum(['bookings as seats_sold' => fn ($q) => $q->whereIn('status', BookingController::PAID)], 'quantity')
                ->orderBy('departure_at')->limit(6)
                ->get(['id', 'origin_city', 'destination_city', 'departure_at', 'transport_mode', 'seats_available', 'discount_percent', 'promo_ends_at', 'is_active'])
                ->map(fn ($o) => [
                    'id' => $o->id, 'route' => $o->origin_city.' → '.$o->destination_city, 'mode' => $o->transport_mode_label,
                    'departure_at' => $o->departure_at?->format('Y-m-d\TH:i'), 'seats' => (int) $o->seats_available,
                    'sold' => (int) ($o->seats_sold ?? 0), 'promo' => $o->discount_percent, 'active' => (bool) $o->is_active,
                ]),
        ]);
    }
}
