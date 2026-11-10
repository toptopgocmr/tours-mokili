<?php

namespace App\Services\Partner;

use App\Models\Divertissement\Event;
use App\Models\Logement\LodgingListing;
use App\Models\Loisirs\AttractionPark;
use App\Models\User;
use App\Models\Voiture\Vehicle;
use App\Models\Voyage\TravelOffer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Offres d'un partenaire, toutes rubriques confondues : trajets et offres
 * vols, logements (hotels, appartements, villas), vehicules (location, vente,
 * garage), evenements (concerts, spectacles, cinema) et parcs. Sert aux
 * promotions et aux reservations de l'espace partenaire.
 */
class PartnerCatalog
{
    /** type => [modele, colonne proprietaire, libelle] */
    public const TYPES = [
        'trajets' => [TravelOffer::class, 'owner_id', 'Trajets et offres vols'],
        'logements' => [LodgingListing::class, 'owner_id', 'Hotels et logements'],
        'vehicules' => [Vehicle::class, 'owner_id', 'Voitures et garage'],
        'evenements' => [Event::class, 'organizer_id', 'Concerts, spectacles et cinema'],
        'parcs' => [AttractionPark::class, 'owner_id', 'Parcs et loisirs'],
    ];

    public function query(string $type, User $user): Builder
    {
        [$model, $col] = self::TYPES[$type];

        return $model::query()->where($col, $user->id);
    }

    /** Ids de toutes les offres du partenaire, par classe de modele. */
    public function ownedIds(User $user): array
    {
        $out = [];
        foreach (self::TYPES as $type => [$model]) {
            $out[$model] = $this->query($type, $user)->pluck('id')->all();
        }

        return $out;
    }

    /** Offres promouvables (a venir pour les trajets / evenements), au format commun. */
    public function promotable(User $user): array
    {
        $groups = [];
        foreach (self::TYPES as $type => [$model, , $label]) {
            $q = $this->query($type, $user);
            if ($type === 'trajets') {
                $q->where(fn ($w) => $w->whereNull('departure_at')->orWhere('departure_at', '>=', now()))->orderBy('departure_at');
            } elseif ($type === 'evenements') {
                $q->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '>=', now()))->orderBy('starts_at');
            } else {
                $q->latest();
            }
            $items = $q->limit(300)->get()->map(fn ($m) => $this->row($type, $m))->values()->all();
            if ($items) {
                $groups[] = ['type' => $type, 'label' => $label, 'items' => $items];
            }
        }

        return $groups;
    }

    public function row(string $type, $m): array
    {
        [$title, $sub, $price, $date] = match ($type) {
            'trajets' => [($m->origin_city ?: 'Brazzaville').' → '.$m->destination_city, $m->type === 'vol' ? 'Offre vol' : $m->transport_mode_label, $m->price, $m->departure_at],
            'logements' => [$m->title, ($m->property_type_label ?? '').' · '.$m->city.' · par nuit', $m->price_per_night, null],
            'vehicules' => [$m->title, $m->offer_type_label ?? '', $m->offer_type === 'location' ? $m->price_per_day : $m->sale_price, null],
            'evenements' => [$m->title, trim(($m->venue ?? '').' · '.($m->city ?? ''), ' ·'), $m->price, $m->starts_at],
            'parcs' => [$m->name, ($m->category_label ?? '').' · billet adulte', $m->price_adult, null],
        };

        return [
            'id' => $m->id,
            'title' => $title,
            'subtitle' => $sub,
            'price' => $price !== null ? (float) $price : null,
            'currency' => $m->currency ?? 'XAF',
            'date' => $date?->format('Y-m-d\TH:i'),
            'discount_percent' => (int) $m->discount_percent,
            'promo_label' => $m->promo_label,
            'promo_ends_at' => $m->promo_ends_at?->toIso8601String(),
        ];
    }
}
