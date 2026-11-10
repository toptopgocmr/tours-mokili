<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// "Reserver ma visite" sur la fiche d'un site touristique : demande sans
// engagement, traitee par l'equipe MOKILI TOUR depuis le back-office.
class VisitRequestController extends Controller
{
    public function store(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->is_active && $business->category === 'site_touristique', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:6', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'people' => ['required', 'integer', 'min:1', 'max:500'],
            'formula' => ['required', Rule::in(array_keys(VisitRequest::FORMULAS))],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $business->hasMany(VisitRequest::class)->create($data + ['user_id' => $request->user()?->id]);

        return back()->with('success', 'Demande de visite envoyee. L\'equipe MOKILI TOUR vous rappelle pour confirmer.');
    }
}
