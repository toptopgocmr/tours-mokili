<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\Partner\PartnerCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace partenaire - PROMOTIONS, toutes rubriques : trajets et offres vols,
 * hotels et logements, voitures (location, vente, garage), concerts /
 * spectacles / cinema, parcs. Remise en %, libelle et date de fin ; a la
 * date de fin la remise ne s'applique plus automatiquement.
 */
class PromotionController extends Controller
{
    public function __construct(protected PartnerCatalog $catalog) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Promotions/Index', ['groups' => $this->catalog->promotable($request->user())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->selectionRules() + [
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'promo_label' => ['nullable', 'string', 'max:60'],
            'promo_ends_at' => ['nullable', 'date', 'after:now'],
        ]);
        $n = $this->apply($request, $data, [
            'discount_percent' => $data['discount_percent'],
            'promo_label' => $data['promo_label'] ?? null,
            'promo_ends_at' => $data['promo_ends_at'] ?? null,
        ]);
        abort_if($n === null, 422, 'Choisissez au moins une offre.');

        return back()->with('success', "Promotion -{$data['discount_percent']} % appliquee a {$n} offre(s).");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate($this->selectionRules());
        $n = $this->apply($request, $data, ['discount_percent' => 0, 'promo_label' => null, 'promo_ends_at' => null]);

        return back()->with('success', 'Promotion arretee sur '.($n ?? 0).' offre(s).');
    }

    protected function selectionRules(): array
    {
        $rules = ['items' => ['array']];
        foreach (array_keys(PartnerCatalog::TYPES) as $t) {
            $rules["items.$t"] = ['array'];
            $rules["items.$t.*"] = ['integer'];
        }
        // Ancien format (offer_ids / vehicle_ids) toujours accepte.
        $rules['offer_ids'] = ['array'];
        $rules['offer_ids.*'] = ['integer'];
        $rules['vehicle_ids'] = ['array'];
        $rules['vehicle_ids.*'] = ['integer'];

        return $rules;
    }

    /** Applique $values aux offres choisies (uniquement celles du partenaire). */
    protected function apply(Request $request, array $data, array $values): ?int
    {
        $items = $data['items'] ?? [];
        $items['trajets'] = array_merge($items['trajets'] ?? [], $data['offer_ids'] ?? []);
        $items['vehicules'] = array_merge($items['vehicules'] ?? [], $data['vehicle_ids'] ?? []);
        if (! array_filter($items)) {
            return null;
        }
        $n = 0;
        foreach (PartnerCatalog::TYPES as $type => $_) {
            if (! empty($items[$type])) {
                $n += $this->catalog->query($type, $request->user())->whereIn('id', $items[$type])->update($values);
            }
        }

        return $n;
    }
}
