<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Marketplace\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for the Flutter app - MARKETPLACE module (follows the
 * Voyage pilot module pattern, see TravelOfferController).
 */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->active()
            ->when($request->filled('q'), fn ($q) => $q->where(
                'title', 'like', '%'.$request->string('q').'%'
            ))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()
            ->paginate(12);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->status === 'published', 404);

        return response()->json($product);
    }

    public function book(StoreBookingRequest $request, Product $product): JsonResponse
    {
        $booking = DB::transaction(function () use ($request, $product) {
            $quantity = $request->integer('quantity');
            $unitPrice = $product->price;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => Product::class,
                'bookable_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
                'currency' => $product->currency,
                'status' => 'awaiting_payment',
                'meta' => ['category' => $product->category],
                'notes' => $request->string('notes'),
            ]);
        });

        return response()->json($booking, 201);
    }
}
