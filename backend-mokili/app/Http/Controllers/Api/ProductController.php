<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Marketplace\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Marketplace\ProductController as WebProductController;

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
            // Petites annonces : je vends / je recherche, ville, etat, prix.
            ->whereNull('sold_at')
            ->when($request->string('listing_type')->isNotEmpty(), fn ($q) => $q->where('listing_type', $request->string('listing_type')))
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->string('condition')->isNotEmpty(), fn ($q) => $q->where('condition', $request->string('condition')))
            ->when($request->filled('price_min'), fn ($q) => $q->where('price', '>=', $request->float('price_min')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('price', '<=', $request->float('price_max')))
            ->tap(fn ($q) => match ($request->input('sort')) {
                'prix_asc' => $q->orderBy('price'),
                'prix_desc' => $q->orderByDesc('price'),
                default => $q->orderByDesc('created_at'),
            })
            ->paginate(12)
            ->withQueryString();

        return response()->json($products);
    }

    /** Listes pour les filtres et le formulaire de l'app (categories, etats, types). */
    public function options(): JsonResponse
    {
        return response()->json([
            'categories' => config('catalogue.marketplace.categories'),
            'conditions' => config('catalogue.marketplace.conditions'),
            'listing_types' => config('catalogue.marketplace.listing_types'),
            'max_photos' => WebProductController::MAX_PHOTOS,
        ]);
    }

    /** "Mes annonces" (app) : toutes les annonces du vendeur, avec leur statut. */
    public function mine(Request $request): JsonResponse
    {
        return response()->json($request->user()->products()->latest()->paginate(30));
    }

    /** Deposer une annonce depuis l'app (memes regles que le site). */
    public function store(Request $request): JsonResponse
    {
        $web = app(WebProductController::class);
        $data = $web->validated($request);
        $photos = $web->storePhotos($request);
        $staff = in_array($request->user()->role, ['admin', 'agent'], true);

        $product = $request->user()->products()->create($data + [
            'image' => $photos[0] ?? null,
            'gallery' => $photos ?: null,
            'currency' => 'XAF',
            'country' => 'CG',
            'stock' => 1,
            'is_active' => true,
            'status' => $staff ? 'published' : 'pending',
        ]);

        return response()->json([
            'message' => $product->status === 'published'
                ? 'Annonce publiee.'
                : 'Annonce envoyee : elle sera visible apres validation par notre equipe (generalement sous 24 h).',
            'product' => $product->fresh(),
        ], 201);
    }

    /** Modifier une annonce (photos ajoutees / retirees), repasse en validation. */
    public function update(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->seller_id === $request->user()->id, 403);
        $web = app(WebProductController::class);
        $data = $web->validated($request);

        $gallery = collect($product->gallery ?? ($product->image ? [$product->image] : []))
            ->reject(fn ($p) => in_array($p, (array) $request->input('remove_photos', []), true))
            ->values();
        foreach (array_diff($product->gallery ?? [], $gallery->all()) as $removed) {
            if (! str_starts_with($removed, 'http')) {
                Storage::disk('public')->delete($removed);
            }
        }
        $gallery = $gallery->merge($web->storePhotos($request, WebProductController::MAX_PHOTOS - $gallery->count()))->values()->all();
        $staff = in_array($request->user()->role, ['admin', 'agent'], true);

        $product->update($data + [
            'image' => $gallery[0] ?? null,
            'gallery' => $gallery ?: null,
            'status' => $staff ? 'published' : 'pending',
            'rejection_reason' => null,
        ]);

        return response()->json(['message' => 'Annonce modifiee : elle repasse en validation.', 'product' => $product->fresh()]);
    }

    public function markSold(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->seller_id === $request->user()->id, 403);
        $product->update(['sold_at' => $product->sold_at ? null : now()]);

        return response()->json([
            'message' => $product->sold_at ? 'Annonce marquee comme vendue.' : 'Annonce remise en ligne.',
            'product' => $product->fresh(),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->seller_id === $request->user()->id, 403);
        foreach ($product->gallery ?? array_filter([$product->image]) as $path) {
            if (! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
        $product->delete();

        return response()->json(['message' => 'Annonce supprimee.']);
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
