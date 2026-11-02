<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Flights\Airport;
use App\Models\Flights\FlightFare;
use App\Models\Flights\FlightSupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Tarifs negocies (fournisseur manuel / consolidateur). */
class FlightFareController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Vols/Fares', [
            'fares' => FlightFare::query()
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('origin', $request->string('q')->upper())
                    ->orWhere('destination', $request->string('q')->upper())
                    ->orWhere('airline_name', 'like', '%'.$request->string('q').'%')))
                ->orderBy('origin')->orderBy('destination')
                ->paginate(25)->withQueryString(),
            'filters' => $request->only('q'),
            'suppliers' => FlightSupplier::orderBy('priority')->get(['id', 'code', 'name', 'driver', 'is_active', 'connection_status']),
            'sandbox' => (bool) config('flights.sandbox_enabled'),
            'pricing' => [
                'markup_percent' => config('flights.markup_percent'),
                'service_fee' => config('flights.service_fee_per_passenger'),
                'offer_ttl' => config('flights.offer_ttl_minutes'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Vols/FareForm', $this->formProps());
    }

    public function store(Request $request): RedirectResponse
    {
        $fare = FlightFare::create($this->validated($request));
        AuditLog::record('flight_fare.create', $fare);

        return redirect()->route('admin.vols.fares.index')->with('success', 'Tarif cree.');
    }

    public function edit(FlightFare $fare): Response
    {
        return Inertia::render('Admin/Vols/FareForm', $this->formProps() + ['fare' => $fare]);
    }

    public function update(Request $request, FlightFare $fare): RedirectResponse
    {
        $fare->update($this->validated($request));
        AuditLog::record('flight_fare.update', $fare);

        return redirect()->route('admin.vols.fares.index')->with('success', 'Tarif mis a jour.');
    }

    public function destroy(FlightFare $fare): RedirectResponse
    {
        AuditLog::record('flight_fare.delete', $fare, $fare->only('origin', 'destination', 'airline_code'));
        $fare->delete();

        return back()->with('success', 'Tarif supprime.');
    }

    public function toggleSupplier(Request $request, FlightSupplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_if($supplier->driver === 'sandbox', 422, 'Le fournisseur sandbox se pilote par FLIGHTS_SANDBOX.');
        $supplier->update(['is_active' => ! $supplier->is_active]);
        AuditLog::record('flight_supplier.toggle', $supplier, ['active' => $supplier->is_active]);

        return back()->with('success', 'Fournisseur '.($supplier->is_active ? 'active' : 'desactive').'.');
    }

    protected function formProps(): array
    {
        return [
            'suppliers' => FlightSupplier::where('driver', 'manual')->get(['id', 'name']),
            'airports' => Airport::orderByDesc('popularity')->get(['iata_code', 'city', 'name']),
            'cabins' => config('flights.cabins'),
        ];
    }

    protected function validated(Request $request): array
    {
        $request->merge([
            'origin' => strtoupper((string) $request->input('origin')),
            'destination' => strtoupper((string) $request->input('destination')),
            'airline_code' => strtoupper((string) $request->input('airline_code')),
        ]);

        return $request->validate([
            'flight_supplier_id' => ['required', 'exists:flight_suppliers,id'],
            'airline_code' => ['required', 'string', 'between:2,3'],
            'airline_name' => ['required', 'string', 'max:120'],
            'flight_number' => ['nullable', 'string', 'max:12'],
            'origin' => ['required', 'size:3', 'exists:airports,iata_code'],
            'destination' => ['required', 'size:3', 'different:origin', 'exists:airports,iata_code'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'arrival_day_offset' => ['required', 'integer', 'between:0,2'],
            'duration_minutes' => ['nullable', 'integer', 'min:20', 'max:3000'],
            'stops' => ['required', 'integer', 'between:0,3'],
            'via' => ['nullable', 'string', 'max:30'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'cabin' => ['required', 'in:'.implode(',', array_keys(config('flights.cabins')))],
            'fare_family' => ['nullable', 'string', 'max:60'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'taxes' => ['required', 'numeric', 'min:0'],
            'child_percent' => ['required', 'integer', 'between:0,100'],
            'infant_percent' => ['required', 'integer', 'between:0,100'],
            'currency' => ['required', 'string', 'size:3'],
            'baggage' => ['nullable', 'string', 'max:60'],
            'cabin_baggage' => ['nullable', 'string', 'max:60'],
            'refundable' => ['boolean'],
            'changeable' => ['boolean'],
            'change_fee' => ['nullable', 'numeric', 'min:0'],
            'cancel_fee' => ['nullable', 'numeric', 'min:0'],
            'conditions' => ['nullable', 'string', 'max:3000'],
            'seats_available' => ['nullable', 'integer', 'min:0'],
            'ticketing_delay_hours' => ['required', 'integer', 'between:2,240'],
            'is_active' => ['boolean'],
        ]);
    }
}
