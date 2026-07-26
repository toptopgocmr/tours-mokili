<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    use HandlesImageUploads;

    public function index(Request $request): Response
    {
        return Inertia::render('Partner/Voiture/Index', [
            'vehicles' => $request->user()->vehicles()->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partner/Voiture/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, null, 'voiture');

        $request->user()->vehicles()->create($data);

        return redirect()->route('partner.voiture.index')->with('success', 'Vehicule publie.');
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorizeOwner($vehicle);

        return Inertia::render('Partner/Voiture/Form', ['vehicle' => $vehicle]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($vehicle);

        $data = $this->validated($request);
        $data['image'] = $this->resolveImagePath($request, $vehicle->image, 'voiture');

        $vehicle->update($data);

        return redirect()->route('partner.voiture.index')->with('success', 'Vehicule mis a jour.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($vehicle);
        $vehicle->delete();

        return back()->with('success', 'Vehicule supprime.');
    }

    protected function authorizeOwner(Vehicle $vehicle): void
    {
        abort_unless($vehicle->owner_id === request()->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'category' => ['required', 'in:citadine,berline,suv,utilitaire,luxe'],
            'transmission' => ['required', 'in:manuelle,automatique'],
            'seats' => ['required', 'integer', 'min:1', 'max:9'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }
}
