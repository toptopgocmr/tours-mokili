<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

// Back-office : demandes "Reserver ma visite" des sites touristiques.
class VisitRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('Admin/Visites/Index', [
            'requests' => VisitRequest::query()->with('business:id,name,slug,city')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("status = 'nouvelle' desc")->orderBy('visit_date')
                ->paginate(25)->withQueryString(),
            'statuses' => VisitRequest::STATUSES,
            'formulas' => VisitRequest::FORMULAS,
            'filters' => ['status' => $status],
            'counts' => VisitRequest::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function update(Request $request, VisitRequest $visit): RedirectResponse
    {
        $visit->update($request->validate(['status' => ['required', Rule::in(array_keys(VisitRequest::STATUSES))]]));

        return back()->with('success', 'Statut mis a jour.');
    }
}
