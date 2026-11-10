<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Back-office : societes partenaires, rangees par rubrique selon leur activite. */
class BusinessController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Partners/Index', [
            'businesses' => Business::query()
                ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
                ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->where('category', $request->string('category')))
                ->orderBy('category')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('search', 'category'),
            'categories' => Business::CATEGORIES,
            'modules' => Business::MODULES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Partners/Form', ['categories' => Business::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->imageFor($request, null);

        Business::create($data);

        return redirect()->route('admin.partners.index')->with('success', 'Societe ajoutee : elle apparait dans sa rubrique.');
    }

    public function edit(Business $business): Response
    {
        return Inertia::render('Admin/Partners/Form', ['business' => $business->makeVisible('preview_image'), 'categories' => Business::CATEGORIES]);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->imageFor($request, $business->image);
        // "Publier la photo" : la societe a donne son accord, l'apercu devient public.
        if ($request->boolean('publish_preview') && $business->preview_image) {
            $data['image'] = $business->preview_image;
            $data['preview_image'] = null;
        }

        $business->update($data);

        return redirect()->route('admin.partners.index')->with('success', 'Fiche mise a jour.');
    }

    /** Mode presentation : photos apercu (admin / agents connectes uniquement). */
    public function previews(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Business::query()->whereNotNull('preview_image')->pluck('preview_image', 'id'));
    }

    public function destroy(Business $business): RedirectResponse
    {
        $business->delete();

        return back()->with('success', 'Fiche supprimee.');
    }

    /** Photo uploadee en priorite, sinon URL saisie, sinon photo existante. */
    protected function imageFor(Request $request, ?string $existing): ?string
    {
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $isLocalFile = $existing && ! str_starts_with($existing, 'http') && ! str_starts_with($existing, '/');

            return $this->resolveImagePath($request, $isLocalFile ? $existing : null, 'partenaires');
        }

        return $request->filled('image_link') ? $request->string('image_link')->toString() : $existing;
    }

    protected function validated(Request $request): array
    {
        if (is_string($request->input('services'))) {
            $request->merge(['services' => collect(preg_split('/[\r\n,]+/', $request->input('services')))
                ->map(fn ($s) => trim($s))->filter()->values()->all()]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Business::CATEGORIES))],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'description' => ['nullable', 'string'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:120'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'price_range' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_link' => ['nullable', 'url', 'max:500'],
            'remove_image' => ['boolean'],
            'preview_image' => ['nullable', 'url', 'max:500'],
        ]);

        unset($data['image'], $data['image_link'], $data['remove_image']);
        $data['country'] = 'CG';

        return $data;
    }
}
