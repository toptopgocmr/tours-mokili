<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Support\DirectoryCountries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ANNUAIRE des entreprises (/annuaire) : toutes les societes visibles, quelle
 * que soit leur rubrique, avec recherche par nom/activite, pays, ville et
 * domaine. Les entreprises s'inscrivent gratuitement (/annuaire/inscription) ;
 * leur fiche est publiee apres validation dans /admin/partenaires.
 */
class DirectoryController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        $filters = self::filters($request);

        $base = Business::query()->active();

        return Inertia::render('Annuaire/Index', [
            'businesses' => self::search($filters)
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->paginate(24)
                ->withQueryString(),
            'filters' => $filters,
            'domains' => self::domains(),
            'countries' => self::countryOptions((clone $base)->selectRaw('country, count(*) as n')->groupBy('country')->pluck('n', 'country')->all()),
            'cities' => (clone $base)
                ->when($filters['pays'] !== '', fn ($q) => $q->where('country', $filters['pays']))
                ->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'total' => (clone $base)->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Annuaire/Register', [
            'domains' => self::domains(),
            'countries' => self::countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Champ piege invisible : rempli uniquement par les robots.
        if ($request->filled('fax')) {
            return redirect()->route('annuaire.index');
        }

        $data = self::validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'partenaires');

        Business::create($data);

        return redirect()->route('annuaire.index')->with('success', 'Merci ! Votre entreprise a ete envoyee. Elle apparaitra dans l\'annuaire apres verification par MOKILI TOUR.');
    }

    /** Recherche commune au site et a l'API mobile. */
    public static function search(array $f): Builder
    {
        return Business::query()->active()
            ->when($f['q'] !== '', function ($q) use ($f) {
                $t = '%'.$f['q'].'%';
                $q->where(fn ($w) => $w->where('name', 'like', $t)
                    ->orWhere('description', 'like', $t)
                    ->orWhere('district', 'like', $t)
                    ->orWhere('services', 'like', $t));
            })
            ->when($f['pays'] !== '', fn ($q) => $q->where('country', $f['pays']))
            ->when($f['ville'] !== '', fn ($q) => $q->where('city', $f['ville']))
            ->when($f['domaine'] !== '', fn ($q) => $q->where('category', $f['domaine']));
    }

    public static function filters(Request $request): array
    {
        $pays = strtoupper((string) $request->query('pays', ''));
        $domaine = (string) $request->query('domaine', '');

        return [
            'q' => trim(mb_substr((string) $request->query('q', ''), 0, 100)),
            'pays' => array_key_exists($pays, DirectoryCountries::all()) ? $pays : '',
            'ville' => trim(mb_substr((string) $request->query('ville', ''), 0, 120)),
            'domaine' => array_key_exists($domaine, Business::CATEGORIES) ? $domaine : '',
        ];
    }

    /** Domaines d'activite, tries par ordre alphabetique ("Autres" a la fin). */
    public static function domains(): array
    {
        return collect(Business::CATEGORIES)
            ->except('autre')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->put('autre', Business::CATEGORIES['autre'])
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /** [{code, name, count}] : nombre d'entreprises visibles par pays. */
    public static function countryOptions(array $counts = []): array
    {
        return collect(DirectoryCountries::all())
            ->map(fn ($name, $code) => ['code' => $code, 'name' => $name, 'count' => (int) ($counts[$code] ?? 0)])
            ->values()
            ->all();
    }

    public static function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Business::CATEGORIES))],
            'country' => ['required', Rule::in(array_keys(DirectoryCountries::all()))],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_person' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'accept' => ['accepted'],
        ], [], [
            'name' => 'nom de l\'entreprise', 'category' => 'domaine d\'activite', 'country' => 'pays',
            'city' => 'ville', 'address' => 'adresse', 'phone' => 'telephone', 'contact_person' => 'personne a contacter',
            'image' => 'logo ou photo', 'accept' => 'confirmation',
        ]);

        unset($data['image'], $data['accept']);

        return $data + ['is_active' => false, 'is_featured' => false, 'submitted_at' => now()];
    }
}
