<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Divertissement\Event;
use App\Models\Fret\FreightOffer;
use App\Models\Logement\LodgingListing;
use App\Models\Marketplace\Product;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voiture\Vehicle;
use App\Models\Voyage\TravelOffer;
use Illuminate\Support\Facades\DB;
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
                'freightOffers' => FreightOffer::query()->count(),
                'bookings' => Booking::query()->count(),
                'bookingsConfirmed' => Booking::query()->where('status', 'confirmed')->count(),
            ],
            'recentBookings' => Booking::query()->with(['user', 'bookable'])->latest()->take(8)->get(),
            'kpis' => $this->kpis(),
        ]);
    }

    /**
     * Dynamic decision-support KPIs: revenue trend, bookings split by
     * module, refund rate, and top partners by revenue - so staff can
     * spot what's growing/shrinking without digging through raw tables.
     */
    private function kpis(): array
    {
        $revenueThisMonth = (float) Payment::query()
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');

        $lastMonth = now()->subMonthNoOverflow();
        $revenueLastMonth = (float) Payment::query()
            ->where('status', 'paid')
            ->whereYear('paid_at', $lastMonth->year)
            ->whereMonth('paid_at', $lastMonth->month)
            ->sum('amount');

        $revenueChangePercent = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100.0 : 0.0);

        $totalPaid = (float) Payment::query()->where('status', 'paid')->sum('amount');
        $totalRefunded = (float) Payment::query()->where('status', 'refunded')->sum('amount');
        $refundRate = ($totalPaid + $totalRefunded) > 0
            ? round(($totalRefunded / ($totalPaid + $totalRefunded)) * 100, 1)
            : 0.0;

        $moduleLabels = [
            TravelOffer::class => 'Voyage',
            LodgingListing::class => 'Logement',
            Vehicle::class => 'Voiture',
            Event::class => 'Divertissement',
            Product::class => 'Marketplace',
            FreightOffer::class => 'Fret',
        ];

        $rawCounts = Booking::query()
            ->select('bookable_type', DB::raw('count(*) as total'))
            ->groupBy('bookable_type')
            ->pluck('total', 'bookable_type');

        $maxCount = max(1, $rawCounts->max() ?? 1);
        $bookingsByModule = collect($moduleLabels)->map(fn ($label, $class) => [
            'label' => $label,
            'count' => (int) ($rawCounts[$class] ?? 0),
            'percent' => round((($rawCounts[$class] ?? 0) / $maxCount) * 100),
        ])->values();

        $topPartners = $this->topPartnersByRevenue();

        return [
            'revenueThisMonth' => $revenueThisMonth,
            'revenueLastMonth' => $revenueLastMonth,
            'revenueChangePercent' => $revenueChangePercent,
            'refundRate' => $refundRate,
            'bookingsByModule' => $bookingsByModule,
            'topPartners' => $topPartners,
        ];
    }

    /**
     * Sums confirmed/completed booking revenue per partner across the 5
     * partner-ownable modules (Voyage is centrally admin-managed, so it
     * has no partner owner and is excluded here). Each module names its
     * owner column differently (owner_id / organizer_id / seller_id /
     * carrier_id), so this loops per-module rather than one generic join.
     */
    private function topPartnersByRevenue(): array
    {
        $ownerColumnByModel = [
            LodgingListing::class => 'owner_id',
            Vehicle::class => 'owner_id',
            Event::class => 'organizer_id',
            Product::class => 'seller_id',
            FreightOffer::class => 'carrier_id',
        ];

        $revenueByOwner = [];

        foreach ($ownerColumnByModel as $modelClass => $ownerColumn) {
            $table = (new $modelClass)->getTable();

            $rows = Booking::query()
                ->join($table, function ($join) use ($table, $modelClass) {
                    $join->on('bookings.bookable_id', '=', "{$table}.id")
                        ->where('bookings.bookable_type', $modelClass);
                })
                ->whereIn('bookings.status', ['confirmed', 'completed'])
                ->select("{$table}.{$ownerColumn} as owner_id", DB::raw('SUM(bookings.total_amount) as revenue'))
                ->groupBy("{$table}.{$ownerColumn}")
                ->get();

            foreach ($rows as $row) {
                $revenueByOwner[$row->owner_id] = ($revenueByOwner[$row->owner_id] ?? 0) + (float) $row->revenue;
            }
        }

        arsort($revenueByOwner);
        $top = array_slice($revenueByOwner, 0, 5, true);

        if (empty($top)) {
            return [];
        }

        $names = User::query()->whereIn('id', array_keys($top))->pluck('name', 'id');

        return collect($top)->map(fn ($revenue, $ownerId) => [
            'name' => $names[$ownerId] ?? 'Partenaire #'.$ownerId,
            'revenue' => $revenue,
        ])->values()->all();
    }
}
