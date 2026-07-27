<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BooksListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Divertissement\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the Flutter app - DIVERTISSEMENT module (billetterie).
 */
class EventController extends Controller
{
    use BooksListings;

    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->active()
            ->where('starts_at', '>=', now()->subHours(6))
            ->when($request->string('city')->isNotEmpty(), fn ($q) => $q->where(
                'city', 'like', '%'.$request->string('city').'%'
            ))
            ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->where(
                'category', $request->string('category')
            ))
            ->orderBy('starts_at')
            ->paginate(10);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event);
    }

    public function book(StoreBookingRequest $request, Event $event): JsonResponse
    {
        abort_unless($event->is_active && $event->status === 'published', 404);

        $booking = $this->createBooking(
            $request,
            $event,
            Event::class,
            (float) $event->price,
            $event->currency,
            $event->starts_at,
            $event->ends_at,
        );

        return response()->json($booking, 201);
    }
}
