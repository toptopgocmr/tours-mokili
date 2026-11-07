<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightAuditLog;
use App\Models\Fret\FreightPartnerProfile;
use App\Services\Fret\FreightPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Back-office : verification des candidatures partenaires (approuver, refuser, corriger, suspendre, reactiver). */
class FreightPartnerController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Fret/Partners/Index', [
            'partners' => FreightPartnerProfile::query()
                ->with('user:id,name,email')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByRaw("case status when 'pending' then 0 when 'needs_correction' then 1 else 2 end")
                ->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
            'statusLabels' => FreightPartnerProfile::LABELS,
        ]);
    }

    public function show(FreightPartnerProfile $profile): Response
    {
        return Inertia::render('Admin/Fret/Partners/Show', [
            'profile' => $profile->load(['user:id,name,email', 'documents']),
            'statusLabels' => FreightPartnerProfile::LABELS,
            'history' => FreightAuditLog::where('subject_type', 'FreightPartnerProfile')->where('subject_id', $profile->id)->orderByDesc('id')->get(),
        ]);
    }

    public function decide(Request $request, FreightPartnerProfile $profile, FreightPartnerService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,correction,suspend,reactivate'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:60'],
        ]);

        try {
            if (array_key_exists('commission_percent', $data) && $data['commission_percent'] !== null) {
                $profile->update(['commission_percent' => $data['commission_percent']]);
            }
            $service->decide($request->user(), $profile, $data['decision'], $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Decision enregistree.');
    }
}
