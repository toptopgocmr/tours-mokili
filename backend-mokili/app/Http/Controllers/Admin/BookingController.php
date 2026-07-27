<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only oversight of every booking across all 6 modules
 * (polymorphic bookable relation - see App\Models\Booking).
 */
class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => Booking::query()
                ->with(['user', 'bookable'])
                ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }
}
