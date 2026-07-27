<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Concerns\HandlesPublishingIntent;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use HandlesImageUploads;
    use HandlesPublishingIntent;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Marketplace/Index', [
            'products' => $request->user()->products()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Marketplace/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'marketplace');
        $data['status'] = $this->resolveStatus($request);

        $request->user()->products()->create($data);

        $message = $data['status'] === 'pending'
            ? 'Produit soumis pour validation par un administrateur.'
            : 'Produit enregistre comme brouillon.';

        return redirect()->route('partner.marketplace.index')->with('success', $message);
    }

    public function edit(Product $product): Response
    {
        $this->authorizeOwner($product);

        return Inertia::render('Partner/Marketplace/Form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($product);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $product->image, 'marketplace');
        $data['status'] = $this->resolveStatus($request, $product->status);
        $data['rejection_reason'] = $data['status'] === 'pending' ? null : $product->rejection_reason;

        $product->update($data);

        return redirect()->route('partner.marketplace.index')->with('success', 'Produit mis a jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeOwner($product);
        $product->delete();

        return back()->with('success', 'Produit supprime.');
    }

    protected function authorizeOwner(Product $product): void
    {
        abort_unless($product->seller_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stock' => ['required', 'integer', 'min:0'],
            'condition' => ['required', 'in:neuf,occasion'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
