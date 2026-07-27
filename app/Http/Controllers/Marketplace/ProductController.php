<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Inertia\Inertia;
use Inertia\Response;

// MARKETPLACE module (skeleton).
class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Marketplace/Index', [
            'products' => Product::query()->active()->latest()->paginate(12),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active && $product->status === 'published', 404);

        return Inertia::render('Marketplace/Show', ['product' => $product]);
    }
}
