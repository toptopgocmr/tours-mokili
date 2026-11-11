<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// "Reserver ma visite" (site touristique) ou "Prendre rendez-vous" (garage,
// atelier d'un concessionnaire) : demande sans engagement, traitee par
// l'equipe MOKILI TOUR depuis le back-office. Utilise par le site et l'API mobile.
class VisitRequestController extends Controller
{
    public function store(Request $request, Business $business): RedirectResponse
    {
        $garage = self::isGarage($business);
        self::createFor($request, $business);

        return back()->with('success', $garage
            ? 'Demande de rendez-vous envoyee. Le garage / l\'equipe MOKILI TOUR vous rappelle sous 24 h pour confirmer.'
            : 'Demande de visite envoyee. L\'equipe MOKILI TOUR vous rappelle pour confirmer.');
    }

    public static function isGarage(Business $business): bool
    {
        return $business->category === 'garage'
            || ($business->category === 'concessionnaire'
                && collect($business->services ?? [])->contains(fn ($s) => (bool) preg_match('/atelier|remorquage/i', (string) $s)));
    }

    public static function isBookable(Business $business): bool
    {
        return $business->is_active && ($business->category === 'site_touristique' || self::isGarage($business));
    }

    public static function createFor(Request $request, Business $business): VisitRequest
    {
        abort_unless(self::isBookable($business), 404);
        $garage = self::isGarage($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:6', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'people' => [$garage ? 'nullable' : 'required', 'integer', 'min:1', 'max:500'],
            'formula' => ['nullable', Rule::in(array_keys(VisitRequest::FORMULAS))],
            'service' => [$garage ? 'required' : 'nullable', 'string', 'max:80'],
            'vehicle' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['people'] ??= 1;
        $data['formula'] ??= 'individuel';

        return $business->hasMany(VisitRequest::class)->create($data + ['user_id' => $request->user('sanctum')?->id ?? $request->user()?->id]);
    }
}
