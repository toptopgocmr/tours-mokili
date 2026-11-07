<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightAuditLog;
use App\Models\Fret\FreightOrder;
use App\Services\Fret\FreightAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Back-office : finance (encaissements, commissions, remboursements, reversements) et indicateurs de performance (cadrage section 13). */
class FreightFinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $paid = FreightOrder::whereNotNull('payment_confirmed_at')->whereBetween('payment_confirmed_at', [$from.' 00:00:00', $to.' 23:59:59']);

        $byPartner = (clone $paid)->where('status', '!=', FreightOrder::CANCELLED)
            ->select('partner_user_id', 'currency',
                DB::raw('count(*) as orders'), DB::raw('sum(total_amount) as billed'), DB::raw('sum(commission_amount) as commission'),
                DB::raw('sum(partner_amount) as partner_due'),
                DB::raw('sum(case when settled_at is not null then partner_amount else 0 end) as settled'))
            ->groupBy('partner_user_id', 'currency')->with('partner:id,name')->get();

        return Inertia::render('Admin/Fret/Finance', [
            'range' => ['from' => $from, 'to' => $to],
            'byPartner' => $byPartner,
            'unsettled' => FreightOrder::whereNotNull('payment_confirmed_at')->whereNull('settled_at')->whereNull('refunded_at')
                ->whereIn('status', [FreightOrder::CONFIRMED])->with('partner:id,name')
                ->get(['id', 'reference', 'partner_user_id', 'partner_amount', 'currency', 'confirmed_at']),
            'refunds' => FreightOrder::whereNotNull('refunded_at')->latest('refunded_at')->limit(20)->get(['id', 'reference', 'total_amount', 'currency', 'refunded_at']),
        ]);
    }

    /** Reversement : marque des commandes confirmees comme reglees au partenaire (reference + trace). */
    public function settle(Request $request): RedirectResponse
    {
        $data = $request->validate(['order_ids' => ['required', 'array', 'min:1'], 'order_ids.*' => ['integer'], 'reference' => ['required', 'string', 'max:60']]);

        $orders = FreightOrder::whereIn('id', $data['order_ids'])->whereNull('settled_at')->whereNull('refunded_at')
            ->whereNotNull('payment_confirmed_at')->where('status', FreightOrder::CONFIRMED)->get();

        DB::transaction(function () use ($orders, $data, $request) {
            foreach ($orders as $o) {
                $o->update(['settled_at' => now(), 'settlement_ref' => $data['reference']]);
                FreightAudit::log('order.settled', $o, 'admin:'.$request->user()->id, null, ['ref' => $data['reference'], 'amount' => (string) $o->partner_amount]);
            }
        });

        return back()->with('success', $orders->count().' commande(s) marquee(s) comme reversee(s).');
    }

    public function kpis(): Response
    {
        $q = fn (string $table) => DB::table($table);
        $accepted = $q('freight_quotes')->where('status', 'accepted')->count();
        $sent = $q('freight_quotes')->count();
        $avgResponseMin = $q('freight_quote_requests')->whereNotNull('partner_ack_at')
            ->selectRaw('avg(timestampdiff(minute, created_at, partner_ack_at)) as m')->value('m');

        return Inertia::render('Admin/Fret/Kpis', [
            'kpis' => [
                'active_partners' => $q('freight_partner_profiles')->where('status', 'approved')->count(),
                'pending_partners' => $q('freight_partner_profiles')->whereIn('status', ['pending', 'needs_correction'])->count(),
                'offers_by_mode' => $q('freight_offers')->where('status', 'published')->select('mode', DB::raw('count(*) as total'))->groupBy('mode')->pluck('total', 'mode'),
                'quote_requests' => $q('freight_quote_requests')->count(),
                'avg_response_minutes' => $avgResponseMin !== null ? round((float) $avgResponseMin) : null,
                'quote_accept_rate' => $sent ? round($accepted * 100 / $sent, 1) : null,
                'confirmed_orders' => $q('freight_orders')->whereIn('status', [FreightOrder::CONFIRMED, FreightOrder::DISPUTE])->count(),
                'picked_up' => $q('freight_shipments')->whereNotNull('picked_up_at')->count(),
                'delivered' => $q('freight_shipments')->where('status', 'livre')->count(),
                'claims' => $q('freight_claims')->count(),
                'open_claims' => $q('freight_claims')->whereIn('status', ['open', 'in_review', 'documents_requested'])->count(),
                'turnover' => (float) $q('freight_orders')->whereNotNull('payment_confirmed_at')->where('status', '!=', FreightOrder::CANCELLED)->sum('total_amount'),
                'commissions' => (float) $q('freight_orders')->whereNotNull('payment_confirmed_at')->where('status', '!=', FreightOrder::CANCELLED)->sum('commission_amount'),
                'refunds' => (float) $q('freight_orders')->whereNotNull('refunded_at')->sum('total_amount'),
            ],
        ]);
    }

    public function audit(Request $request): Response
    {
        return Inertia::render('Admin/Fret/Audit', [
            'logs' => FreightAuditLog::query()
                ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->string('action').'%'))
                ->orderByDesc('id')->paginate(50)->withQueryString(),
            'filters' => $request->only('action'),
        ]);
    }
}
