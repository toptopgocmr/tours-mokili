<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Divertissement\Event;
use App\Models\Fret\FreightOffer;
use App\Models\Logement\LodgingListing;
use App\Models\Marketplace\Product;
use App\Models\Voiture\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unified moderation queue for every partner-ownable listing (Logement,
 * Voiture, Divertissement, Marketplace, Fret - Voyage excluded, it's
 * centrally admin-managed). A partner submits a listing (status
 * 'pending', see HandlesPublishingIntent); staff approve it here
 * (-> 'published', visible on the public site) or reject it with a
 * reason (-> 'rejected', partner can edit and resubmit).
 */
class ModerationController extends Controller
{
    /** @var array<string, array{model: class-string, owner: string, label: string}> */
    private const MODULES = [
        'logement' => ['model' => LodgingListing::class, 'owner' => 'owner', 'label' => 'Logement'],
        'voiture' => ['model' => Vehicle::class, 'owner' => 'owner', 'label' => 'Voiture'],
        'divertissement' => ['model' => Event::class, 'owner' => 'organizer', 'label' => 'Divertissement'],
        'marketplace' => ['model' => Product::class, 'owner' => 'seller', 'label' => 'Marketplace'],
        'fret' => ['model' => FreightOffer::class, 'owner' => 'carrier', 'label' => 'Fret'],
    ];

    public function index(): Response
    {
        $pending = collect(self::MODULES)->flatMap(function (array $config, string $slug) {
            return $config['model']::query()
                ->pendingReview()
                ->with($config['owner'])
                ->latest()
                ->get()
                ->map(fn ($item) => [
                    'module' => $slug,
                    'moduleLabel' => $config['label'],
                    'id' => $item->id,
                    'title' => $item->title,
                    'owner' => $item->{$config['owner']}?->name,
                    'city' => $item->city ?? $item->venue ?? $item->origin_city ?? null,
                    'created_at' => $item->created_at,
                ]);
        })->sortBy('created_at')->values();

        return Inertia::render('Admin/Moderation/Index', ['pending' => $pending]);
    }

    public function approve(Request $request, string $module, int $id): RedirectResponse
    {
        $item = $this->findOrFail($module, $id);
        $item->update(['status' => 'published', 'rejection_reason' => null]);

        return back()->with('success', ucfirst($module).' approuve et publie.');
    }

    public function reject(Request $request, string $module, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $item = $this->findOrFail($module, $id);
        $item->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        return back()->with('success', ucfirst($module).' rejete - le partenaire peut corriger et resoumettre.');
    }

    private function findOrFail(string $module, int $id)
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);

        return self::MODULES[$module]['model']::query()->findOrFail($id);
    }
}
