<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Voyage\TravelOffer;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Back-office landing page for admin/agent staff (see
 * routes/web.php "admin." group, guarded by middleware('role:admin,agent')).
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->where('role', 'client')->count(),
                'partners' => User::query()->where('role', 'partner')->count(),
                'agents' => User::query()->where('role', 'agent')->count(),
                'travelOffers' => TravelOffer::query()->count(),
                'bookings' => Booking::query()->count(),
                'bookingsConfirmed' => Booking::query()->where('status', 'confirmed')->count(),
            ],
            'recentBookings' => Booking::query()->with(['user', 'bookable'])->latest()->take(8)->get(),
        ]);
    }
}
