<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Concerns\HandlesPublishingIntent;
use App\Http\Controllers\Controller;
use App\Models\Fret\FreightOffer;
use App\Models\Fret\FreightPartnerProfile;
use App\Services\Fret\FreightPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FreightOfferController extends Controller
{
    use HandlesImageUploads;
    use HandlesPublishingIntent;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Fret/Index', [
            'offers' => $request->user()->freightOffers()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Fret/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'fret');
        $data['status'] = $this->resolveStatus($request);

        // Pas de publication sans partenaire approuve : l'offre reste brouillon.
        $approved = FreightPartnerProfile::where('user_id', $request->user()->id)->where('status', 'approved')->exists();
        if ($data['status'] === 'pending' && ! $approved) {
            $data['status'] = 'draft';
            session()->flash('error', 'Votre candidature partenaire doit etre approuvee avant de soumettre une offre : offre enregistree comme brouillon.');
        }

        $request->user()->freightOffers()->create($data);

        $message = $data['status'] === 'pending'
            ? 'Offre de fret soumise pour validation par un administrateur.'
            : 'Offre de fret enregistree comme brouillon.';

        return redirect()->route('partner.fret.index')->with('success', $message);
    }

    public function edit(FreightOffer $offer): Response
    {
        $this->authorizeOwner($offer);

        return Inertia::render('Partner/Fret/Form', ['offer' => $offer]);
    }

    public function update(Request $request, FreightOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $offer->image, 'fret');
        $data['status'] = $this->resolveStatus($request, $offer->status);
        $data['rejection_reason'] = $data['status'] === 'pending' ? null : $offer->rejection_reason;

        $approved = FreightPartnerProfile::where('user_id', $request->user()->id)->where('status', 'approved')->exists();
        if ($data['status'] === 'pending' && ! $approved) {
            $data['status'] = $offer->status === 'pending' ? 'draft' : $offer->status;
        }

        $offer->fill($data);
        $changed = $offer->getDirty();
        $offer->save();

        // Modification sensible (tarif, destination, conditions) d'une offre publiee : nouvelle validation requise.
        app(FreightPartnerService::class)->resubmitIfSensitive($offer, $changed);

        return redirect()->route('partner.fret.index')->with('success', $offer->status === 'pending' && isset($changed['price_per_kg'])
            ? 'Offre mise a jour : une modification sensible impose une nouvelle validation avant republication.'
            : 'Offre de fret mise a jour.');
    }

    public function destroy(FreightOffer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);
        $offer->delete();

        return back()->with('success', 'Offre de fret supprimee.');
    }

    protected function authorizeOwner(FreightOffer $offer): void
    {
        abort_unless($offer->carrier_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'in:air,mer,route'],
            'origin_city' => ['required', 'string', 'max:255'],
            'origin_country' => ['required', 'string', 'size:2'],
            'destination_city' => ['required', 'string', 'max:255'],
            'destination_country' => ['required', 'string', 'size:2'],
            'price_per_kg' => ['required', 'numeric', 'min:0'],  // 0 autorise pour une offre sur devis
            'currency' => ['required', 'string', 'size:3'],
            'capacity_kg' => ['nullable', 'integer', 'min:0'],
            'package_types' => ['required', 'array', 'min:1'],
            'package_types.*' => ['in:'.implode(',', array_keys(config('catalogue.fret.package_types')))],
            'transit_time' => ['nullable', 'string', 'max:60'],
            'service_type' => ['required', 'in:colis,marchandises,vehicule,conteneur,groupage'],
            'pricing_mode' => ['required', 'in:instant,quote'],
            'price_is_firm' => ['boolean'],
            'unit' => ['required', 'in:kg,m3,tonne,forfait'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'transit_days_min' => ['nullable', 'integer', 'min:0', 'max:365'],
            'transit_days_max' => ['nullable', 'integer', 'min:0', 'max:365', 'gte:transit_days_min'],
            'inclusions' => ['nullable', 'string', 'max:2000'],
            'exclusions' => ['nullable', 'string', 'max:2000'],
            'restrictions' => ['nullable', 'string', 'max:2000'],
            'promo_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'promo_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
