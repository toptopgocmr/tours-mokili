<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Concerns\RepeatsTrips;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voyage\TravelOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace partenaire - TRANSPORT : compagnies de bus, train, canots rapides,
 * taxis (trajets avec horaires et places) et compagnies aeriennes / agences
 * de billets (offres vols du moment). Chaque partenaire ne voit et ne modifie
 * que ses propres offres (owner_id).
 *
 * Tant que le partenaire n'est pas valide par MOKILI TOUR, ses offres sont
 * enregistrees mais masquees (is_active = false) ; elles passent en ligne
 * a la validation (Admin > Agents & Partenaires > Valider).
 */
class TransportController extends Controller
{
    use HandlesImageUploads;
    use RepeatsTrips;

    /** Moyens proposes aux partenaires (les vols passent par "Offres vols"). */
    public const RIDE_MODES = ['bus', 'train', 'canot', 'taxi', 'bateau', 'voiture'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = in_array($request->query('vue'), ['passes', 'vols'], true) ? $request->query('vue') : 'a_venir';

        $offers = $user->travelOffers()
            ->withCount(['bookings as bookings_count' => fn ($q) => $q->whereIn('status', ['confirmed', 'paid', 'completed'])])
            ->withSum(['bookings as seats_sold' => fn ($q) => $q->whereIn('status', ['confirmed', 'paid', 'completed'])], 'quantity')
            ->when($tab === 'vols', fn ($q) => $q->where('type', 'vol'))
            ->when($tab !== 'vols', fn ($q) => $q->where('type', 'circuit'))
            ->when($tab === 'a_venir', fn ($q) => $q->where(fn ($w) => $w->whereNull('departure_at')->orWhere('departure_at', '>=', now()->subHours(2))))
            ->when($tab === 'passes', fn ($q) => $q->where('departure_at', '<', now()->subHours(2)))
            ->when($request->string('q')->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $w
                ->where('origin_city', 'like', '%'.$request->string('q').'%')
                ->orWhere('destination_city', 'like', '%'.$request->string('q').'%')
                ->orWhere('title', 'like', '%'.$request->string('q').'%')))
            ->orderBy('departure_at', $tab === 'passes' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Partner/Transport/Index', [
            'offers' => $offers,
            'tab' => $tab,
            'filters' => $request->only('q'),
            'verified' => $user->isVerifiedPartner(),
            'stats' => [
                'upcoming' => $user->travelOffers()->where('type', 'circuit')->where('departure_at', '>=', now())->count(),
                'flights' => $user->travelOffers()->where('type', 'vol')->count(),
                'promos' => $user->travelOffers()->where('discount_percent', '>', 0)->where(fn ($w) => $w->whereNull('promo_ends_at')->orWhere('promo_ends_at', '>', now()))->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $from = $request->integer('copie') ? $user->travelOffers()->find($request->integer('copie')) : null;

        return Inertia::render('Partner/Transport/Form', [
            'offer' => $from ? collect($from->toArray())->except(['id', 'slug', 'image', 'image_url'])->all() : null,
            'kind' => $request->query('type') === 'vol' || $from?->type === 'vol' ? 'vol' : 'circuit',
            ...$this->formOptions($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request, $user);
        $data['image'] = $this->resolveImagePath($request, null, 'voyage');
        $data['owner_id'] = $user->id;

        $first = TravelOffer::create($data);
        $copies = $this->repeatTrip($request, $first);

        $msg = $first->type === 'vol' ? 'Offre vol enregistree.' : ($copies ? 'Trajet cree pour '.($copies + 1).' departs.' : 'Trajet enregistre.');
        if (! $user->isVerifiedPartner()) {
            $msg .= ' Il sera visible des que MOKILI TOUR aura valide votre compte.';
        }

        return redirect()->route('partner.transport.index', $first->type === 'vol' ? ['vue' => 'vols'] : [])->with('success', $msg);
    }

    public function edit(Request $request, TravelOffer $offer): Response
    {
        $this->authorizeOwner($request, $offer);

        return Inertia::render('Partner/Transport/Form', [
            'offer' => $offer,
            'kind' => $offer->type === 'vol' ? 'vol' : 'circuit',
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(Request $request, TravelOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($request, $offer);
        $data = $this->validated($request, $request->user(), $offer);
        $data['image'] = $this->resolveImagePath($request, $offer->image, 'voyage');

        $offer->update($data);

        return redirect()->route('partner.transport.index', $offer->type === 'vol' ? ['vue' => 'vols'] : [])->with('success', 'Offre mise a jour.');
    }

    public function destroy(Request $request, TravelOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($request, $offer);
        // Des voyageurs ont paye : on masque l'offre au lieu de la supprimer.
        if ($offer->bookings()->whereIn('status', ['confirmed', 'paid', 'completed', 'awaiting_payment'])->exists()) {
            $offer->update(['is_active' => false]);

            return back()->with('success', 'Ce trajet a des reservations : il a ete masque (il n\'est plus en vente) au lieu d\'etre supprime.');
        }
        $offer->delete();

        return back()->with('success', 'Offre supprimee.');
    }

    /** Liste des passagers d'un trajet (feuille d'embarquement). */
    public function passengers(Request $request, TravelOffer $offer): Response
    {
        $this->authorizeOwner($request, $offer);

        return Inertia::render('Partner/Transport/Passengers', [
            'offer' => $offer,
            'bookings' => $offer->bookings()->with('user:id,name,phone,email')
                ->whereIn('status', ['confirmed', 'paid', 'completed', 'awaiting_payment'])
                ->latest()->get(['id', 'reference', 'user_id', 'quantity', 'total_amount', 'currency', 'status', 'notes', 'created_at']),
        ]);
    }

    protected function formOptions(User $user): array
    {
        return [
            'modes' => collect(TravelOffer::TRANSPORT_MODES)->only(self::RIDE_MODES)->all(),
            'defaultMode' => User::ACTIVITY_MODES[$user->partner_activity] ?? 'bus',
            'company' => $user->company_name ?: $user->name,
            'tripScopes' => config('catalogue.voyage.trip_scopes'),
            'departments' => config('catalogue.departments'),
            'verified' => $user->isVerifiedPartner(),
        ];
    }

    protected function validated(Request $request, User $user, ?TravelOffer $offer = null): array
    {
        $isFlight = ($offer?->type ?? $request->input('type')) === 'vol';
        $request->merge(['type' => $isFlight ? 'vol' : 'circuit']);
        if ($isFlight) {
            $request->merge(['transport_mode' => 'avion']);
        }

        $data = $request->validate([
            'type' => ['required', 'in:vol,circuit'],
            'transport_mode' => ['required', 'in:'.($isFlight ? 'avion' : implode(',', self::RIDE_MODES))],
            'title' => ['nullable', 'string', 'max:255'],
            'origin_city' => ['required', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],
            'destination_country' => ['nullable', 'string', 'size:2'],
            'departure_point' => ['nullable', 'string', 'max:255'],
            'trip_scope' => ['nullable', 'in:'.implode(',', array_keys(config('catalogue.voyage.trip_scopes')))],
            'airline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'departure_at' => [$isFlight ? 'nullable' : 'required', 'date'],
            'arrival_at' => ['nullable', 'date', 'after_or_equal:departure_at'],
            'return_at' => ['nullable', 'date', 'after_or_equal:departure_at'],
            'price' => ['required', 'numeric', 'min:0'],
            'seats_available' => ['required', 'integer', 'min:0', 'max:2000'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'promo_label' => ['nullable', 'string', 'max:60'],
            'promo_ends_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);

        $data['airline'] = ($data['airline'] ?? '') ?: ($user->company_name ?: $user->name);
        $data['destination_country'] = strtoupper($data['destination_country'] ?? '') ?: 'CG';
        $data['currency'] = 'XAF';
        $data['discount_percent'] = (int) ($data['discount_percent'] ?? 0);
        $data['title'] = ($data['title'] ?? '') ?: trim(($data['origin_city'] ?? '').' - '.$data['destination_city']);
        // Partenaire non valide : enregistre mais pas encore en vente.
        $data['is_active'] = $user->isVerifiedPartner() ? (bool) ($data['is_active'] ?? true) : false;
        unset($data['remove_image']);

        return $data;
    }

    protected function authorizeOwner(Request $request, TravelOffer $offer): void
    {
        abort_unless($offer->owner_id === $request->user()->id, 403);
    }
}
