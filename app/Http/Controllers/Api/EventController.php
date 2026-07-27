<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Divertissement\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON API for the Flutter app - DIVERTISSEMENT module (follows the
 * Voyage pilot module pattern, see TravelOfferController).
 */
class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->active()
            ->when($request->filled('city'), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('starts_at')
            ->paginate(10);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        return response()->json($event);
    }

    public function book(StoreBookingRequest $request, Event $event): JsonResponse
    {
        $booking = DB::transaction(function () use ($request, $event) {
            $tickets = $request->integer('quantity');
            $unitPrice = $event->price;

            return Booking::create([
                'user_id' => Auth::id(),
                'bookable_type' => Event::class,
                'bookable_id' => $event->id,
                'quantity' => $tickets,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $tickets,
                'currency' => $event->currency,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'status' => 'awaiting_payment',
                'meta' => ['venue' => $event->venue, 'city' => $event->city],
                'notes' => $request->string('notes'),
            ]);
        });

        return response()->json($booking, 201);
    }
}
