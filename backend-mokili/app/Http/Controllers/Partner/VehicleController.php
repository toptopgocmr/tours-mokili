<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Concerns\HandlesImageUploads;
use App\Http\Controllers\Concerns\HandlesPublishingIntent;
use App\Http\Controllers\Controller;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    use HandlesImageUploads;
    use HandlesPublishingIntent;

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
        $data['gallery'] = $this->gallery($request, []);
        $data['status'] = $this->resolveStatus($request);

        $request->user()->vehicles()->create($data);

        $message = $data['status'] === 'pending'
            ? 'Vehicule soumis pour validation par un administrateur.'
            : 'Vehicule enregistre comme brouillon.';

        return redirect()->route('partner.voiture.index')->with('success', $message);
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
        $data['gallery'] = $this->gallery($request, $vehicle->gallery ?? []);
        $data['status'] = $this->resolveStatus($request, $vehicle->status);
        $data['rejection_reason'] = $data['status'] === 'pending' ? null : $vehicle->rejection_reason;

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

    /** Galerie : photos ajoutees (max 15 au total), photos retirees. */
    protected function gallery(Request $request, array $existing): array
    {
        $remove = (array) $request->input('remove_gallery', []);
        foreach (array_intersect($existing, $remove) as $path) {
            if (! str_starts_with($path, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        }
        $kept = array_values(array_diff($existing, $remove));
        foreach ((array) $request->file('gallery', []) as $file) {
            if (count($kept) >= 15) {
                break;
            }
            $kept[] = $file->store('voiture', 'public');
        }

        return $kept;
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'gallery' => ['nullable', 'array', 'max:15'],
            'gallery.*' => ['image', 'max:4096'],
            'remove_gallery' => ['nullable', 'array'],
            'version' => ['nullable', 'string', 'max:120'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'offer_type' => ['required', 'in:'.implode(',', array_keys(config('catalogue.voiture.offer_types')))],
            'description' => ['nullable', 'string', 'max:3000'],
            'sale_price' => ['nullable', 'required_if:offer_type,vente', 'numeric', 'min:0'],
            'mileage_km' => ['nullable', 'integer', 'min:0'],
            'fuel' => ['nullable', 'in:'.implode(',', array_keys(config('catalogue.voiture.fuels')))],
            'color' => ['nullable', 'in:'.implode(',', array_keys(config('catalogue.voiture.colors')))],
            'doors' => ['nullable', 'integer', 'between:2,5'],
            'condition' => ['nullable', 'in:'.implode(',', array_keys(config('catalogue.voiture.conditions')))],
            'first_owner' => ['boolean'],
            'engine' => ['nullable', 'string', 'max:60'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:60'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('catalogue.voiture.categories')))],
            'transmission' => ['required', 'in:manuelle,automatique'],
            'seats' => ['required', 'integer', 'min:1', 'max:9'],
            'price_per_day' => ['nullable', 'required_if:offer_type,location', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
        unset($data['gallery'], $data['remove_gallery']);

        return $data;
    }
}
