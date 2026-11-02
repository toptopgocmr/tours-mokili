<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Loisirs\AttractionPark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Back-office : CRUD des parcs d'attractions / sites de loisirs (LOISIRS). */
class AttractionParkController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Loisirs/Index', [
            'parks' => AttractionPark::query()
                ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(
                    'name', 'like', '%'.$request->string('search').'%'
                ))
                ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where('city', $request->string('city')))
                ->orderBy('city')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search', 'city'),
            'categories' => AttractionPark::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Loisirs/Form', [
            'categories' => AttractionPark::CATEGORIES,
            'cities' => AttractionPark::CITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'loisirs');

        AttractionPark::create($data);

        return redirect()->route('admin.loisirs.index')->with('success', 'Parc ajoute avec succes.');
    }

    public function edit(AttractionPark $park): Response
    {
        return Inertia::render('Admin/Loisirs/Form', [
            'park' => $park,
            'categories' => AttractionPark::CATEGORIES,
            'cities' => AttractionPark::CITIES,
        ]);
    }

    public function update(Request $request, AttractionPark $park): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $park->image, 'loisirs');

        $park->update($data);

        return redirect()->route('admin.loisirs.index')->with('success', 'Parc mis a jour.');
    }

    public function destroy(AttractionPark $park): RedirectResponse
    {
        $park->delete();

        return back()->with('success', 'Parc supprime.');
    }

    protected function validated(Request $request): array
    {
        // Les activites arrivent en texte libre (une par ligne ou separees
        // par des virgules) depuis le formulaire.
        if (is_string($request->input('activities'))) {
            $request->merge([
                'activities' => collect(preg_split('/[\r\n,]+/', $request->input('activities')))
                    ->map(fn ($a) => trim($a))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(AttractionPark::CATEGORIES))],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activities' => ['nullable', 'array'],
            'activities.*' => ['string', 'max:120'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'price_adult' => ['nullable', 'numeric', 'min:0'],
            'price_child' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'min_age' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);

        unset($data['image'], $data['remove_image']);
        $data['country'] = 'CG';

        return $data;
    }
}
