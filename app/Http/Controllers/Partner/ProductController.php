<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
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
        $request->user()->products()->create($this->validated($request));

        return redirect()->route('partner.marketplace.index')->with('success', 'Produit publie.');
    }

    public function edit(Product $product): Response
    {
        $this->authorizeOwner($product);

        return Inertia::render('Partner/Marketplace/Form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($product);
        $product->update($this->validated($request));

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
        ]);
    }
}
