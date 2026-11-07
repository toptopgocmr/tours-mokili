<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightPartnerProfile;
use App\Services\Fret\FreightPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Candidature et profil du partenaire fret (validation par le staff). */
class FreightProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $profile = FreightPartnerProfile::with('documents')->where('user_id', $request->user()->id)->first();

        return Inertia::render('Partner/Fret/Profile', [
            'profile' => $profile,
            'statusLabels' => FreightPartnerProfile::LABELS,
            'documentTypes' => config('freight.partner_document_types'),
        ]);
    }

    public function store(Request $request, FreightPartnerService $service): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'zones' => ['nullable', 'array'],
            'zones.*' => ['string', 'max:80'],
            'modes' => ['required', 'array', 'min:1'],
            'modes.*' => ['in:air,mer,route'],
        ]);

        $service->submit($request->user(), $data);

        return back()->with('success', 'Candidature enregistree : elle est en cours de verification.');
    }
}
