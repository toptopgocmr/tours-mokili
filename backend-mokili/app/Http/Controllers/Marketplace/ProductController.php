<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE : petites annonces d'achat et de vente entre particuliers et
 * professionnels (modele Leboncoin / Facebook Marketplace). Tout client
 * connecte peut deposer une annonce ; elle est publiee apres validation
 * (/admin/moderation). L'acheteur contacte le vendeur par telephone ou
 * WhatsApp.
 */
class ProductController extends Controller
{
    public const MAX_PHOTOS = 6;

    public function index(Request $request): Response
    {
        $sort = $request->string('sort')->toString();

        return Inertia::render('Marketplace/Index', [
            'products' => Product::query()
                ->active()
                ->whereNull('sold_at')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('title', 'like', '%'.$request->string('q').'%')
                    ->orWhere('description', 'like', '%'.$request->string('q').'%')))
                ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
                ->when($request->filled('listing_type'), fn ($q) => $q->where('listing_type', $request->string('listing_type')))
                ->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->string('condition')))
                ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
                ->when($request->filled('price_min'), fn ($q) => $q->where('price', '>=', $request->float('price_min')))
                ->when($request->filled('price_max'), fn ($q) => $q->where('price', '<=', $request->float('price_max')))
                ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'), fn ($q) => $q->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'), fn ($q) => $q->latest()))
                ->paginate(24)
                ->withQueryString(),
            'filters' => (object) $request->only('q', 'category', 'listing_type', 'condition', 'city', 'price_min', 'price_max', 'sort'),
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        $isOwner = $request->user()?->id === $product->seller_id;
        $isStaff = in_array($request->user()?->role, ['admin', 'agent'], true);
        abort_unless(($product->is_active && $product->status === 'published') || $isOwner || $isStaff, 404);

        if (! $isOwner) {
            $product->increment('views');
        }

        return Inertia::render('Marketplace/Show', [
            'product' => $product,
            'seller' => $product->seller ? [
                'name' => $product->contact_name ?: $product->seller->name,
                'since' => $product->seller->created_at?->year,
                'listings' => $product->seller->products()->where('status', 'published')->whereNull('sold_at')->count(),
            ] : null,
            'isOwner' => $isOwner,
            'similar' => Product::query()->active()->whereNull('sold_at')
                ->where('category', $product->category)->whereKeyNot($product->id)
                ->latest()->limit(4)->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Marketplace/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $photos = $this->storePhotos($request);

        $product = $request->user()->products()->create($data + [
            'image' => $photos[0] ?? null,
            'gallery' => $photos ?: null,
            'currency' => 'XAF',
            'country' => 'CG',
            'stock' => 1,
            'is_active' => true,
            // Les annonces des clients sont relues avant publication.
            'status' => $this->isStaff($request) ? 'published' : 'pending',
        ]);

        return redirect()->route('marketplace.mine')->with('success', $product->status === 'published'
            ? 'Annonce publiee.'
            : 'Annonce envoyee : elle sera visible apres validation par notre equipe (generalement sous 24 h).');
    }

    public function mine(Request $request): Response
    {
        return Inertia::render('Marketplace/Mine', [
            'products' => $request->user()->products()->latest()->paginate(20),
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorizeOwner($request, $product);

        return Inertia::render('Marketplace/Form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        $data = $this->validated($request);

        $gallery = collect($product->gallery ?? ($product->image ? [$product->image] : []))
            ->reject(fn ($p) => in_array($p, (array) $request->input('remove_photos', []), true))
            ->values();
        foreach (array_diff($product->gallery ?? [], $gallery->all()) as $removed) {
            Storage::disk('public')->delete($removed);
        }
        $gallery = $gallery->merge($this->storePhotos($request, self::MAX_PHOTOS - $gallery->count()))->values()->all();

        $product->update($data + [
            'image' => $gallery[0] ?? null,
            'gallery' => $gallery ?: null,
            'status' => $this->isStaff($request) ? 'published' : 'pending',
            'rejection_reason' => null,
        ]);

        return redirect()->route('marketplace.mine')->with('success', 'Annonce modifiee : elle repasse en validation.');
    }

    public function markSold(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        $product->update(['sold_at' => $product->sold_at ? null : now()]);

        return back()->with('success', $product->sold_at ? 'Annonce marquee comme vendue.' : 'Annonce remise en ligne.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        foreach ($product->gallery ?? array_filter([$product->image]) as $path) {
            if (! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
        $product->delete();

        return redirect()->route('marketplace.mine')->with('success', 'Annonce supprimee.');
    }

    protected function validated(Request $request): array
    {
        return \Illuminate\Support\Arr::except($request->validate([
            'listing_type' => ['required', 'in:'.implode(',', array_keys(config('catalogue.marketplace.listing_types')))],
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('catalogue.marketplace.categories')))],
            'description' => ['required', 'string', 'min:10', 'max:4000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'negotiable' => ['boolean'],
            'condition' => ['required', 'in:'.implode(',', array_keys(config('catalogue.marketplace.conditions')))],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
        ], [
            'contact_phone.required' => 'Indiquez un numero pour que les acheteurs puissent vous joindre.',
            'description.min' => 'Decrivez votre annonce en quelques mots (10 caracteres minimum).',
        ]), ['photos', 'remove_photos']);
    }

    protected function storePhotos(Request $request, int $limit = self::MAX_PHOTOS): array
    {
        return collect($request->file('photos', []))
            ->take(max(0, $limit))
            ->map(fn ($file) => $file->store('marketplace', 'public'))
            ->values()
            ->all();
    }

    protected function authorizeOwner(Request $request, Product $product): void
    {
        abort_unless($product->seller_id === $request->user()->id, 403);
    }

    protected function isStaff(Request $request): bool
    {
        return in_array($request->user()->role, ['admin', 'agent'], true);
    }
}
