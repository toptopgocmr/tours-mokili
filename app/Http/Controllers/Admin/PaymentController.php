<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight of every payment attempt across all 6 modules
 * (Payment belongsTo Booking, which is itself polymorphic - see
 * App\Models\Booking / App\Models\Payment). Lets staff track paid /
 * failed / refunded transactions and issue refunds.
 *
 * Note: refunding here only updates our own records (status, reason,
 * timestamp) - Peex's documented API doesn't expose a refund endpoint,
 * so any actual money movement back to the customer happens outside
 * the platform (mobile money agent, bank transfer, etc.) and this is
 * where staff log that it happened.
 */
class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Payments/Index', [
            'payments' => Payment::query()
                ->with(['user', 'booking.bookable'])
                ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'stats' => [
                'totalPaid' => Payment::query()->where('status', 'paid')->sum('amount'),
                'totalRefunded' => Payment::query()->where('status', 'refunded')->sum('amount'),
                'pendingCount' => Payment::query()->whereIn('status', ['pending', 'processing'])->count(),
                'failedCount' => Payment::query()->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        abort_unless($payment->status === 'paid', 422, 'Seul un paiement effectue peut etre rembourse.');

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => $data['reason'],
        ]);

        $payment->booking?->update(['status' => 'refunded']);

        return back()->with('success', 'Paiement marque comme rembourse.');
    }
}
