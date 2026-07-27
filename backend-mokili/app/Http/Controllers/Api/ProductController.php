<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Marketplace\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - MARKETPLACE module (style Amazon:
 * grille de produits, fiche detail, commande).
 */
class ProductController extends Controller
{
    use BooksListings;

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->active()
            ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->where(
                'category', $request->string('category')
            ))
            ->when($request->string('q')->isNotEmpty(), fn ($q) => $q->where(
                'title', 'like', '%'.$request->string('q').'%'
            ))
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function book(StoreBookingRequest $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->status === 'published', 404);

        $quantity = max(1, (int) $request->integer('quantity', 1));

        if ($product->stock < $quantity) {
            return response()->json([
                'message' => 'Stock insuffisant pour la quantite demandee.',
            ], 422);
        }

        $booking = $this->createBooking(
            $request,
            $product,
            Product::class,
            (float) $product->price,
            $product->currency,
        );

        $product->decrement('stock', $quantity);

        return response()->json($booking, 201);
    }
}
