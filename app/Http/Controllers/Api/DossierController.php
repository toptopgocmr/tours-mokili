<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDossierRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Services\NumeroGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DossierController extends Controller
{
    /**
     * Liste des dossiers.
     * - Un agent ne voit que ses propres dossiers.
     * - Un admin voit tout.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Dossier::with(['client', 'agent'])->withCount(['chargeItems', 'documents'])->latest('date_intervention');

        if ($user->isAgent()) {
            $query->where('agent_id', $user->id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('numero_dossier', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$q}%"));
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function show(Request $request, Dossier $dossier)
    {
        $this->autoriserAcces($request, $dossier);

        $dossier->load(['client', 'agent', 'chargeItems', 'documents', 'devis', 'factures']);

        return response()->json($dossier);
    }

    public function store(StoreDossierRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $dossier = DB::transaction(function () use ($data, $user) {
            $client = $this->resoudreClient($data['client'] ?? []);

            $dossier = Dossier::create([
                'numero_dossier' => NumeroGeneratorService::dossier(),
                'client_id' => $client->id,
                'agent_id' => $user->id,
                'date_intervention' => $data['date_intervention'],
                'heure_intervention' => $data['heure_intervention'] ?? null,
                'responsable_technique' => $data['responsable_technique'] ?? $user->name,
                'type_site' => $data['type_site'] ?? null,
                'niveaux' => $data['niveaux'] ?? null,
                'objectifs' => $data['objectifs'] ?? [],
                'type_reseau' => $data['type_reseau'] ?? null,
                'tension_v' => $data['tension_v'] ?? null,
                'frequence_hz' => $data['frequence_hz'] ?? null,
                'puissance_souscrite_kva' => $data['puissance_souscrite_kva'] ?? null,
                'facture_e2c' => $data['facture_e2c'] ?? null,
                'groupe_marque' => $data['groupe_marque'] ?? null,
                'groupe_puissance_kva' => $data['groupe_puissance_kva'] ?? null,
                'groupe_annee' => $data['groupe_annee'] ?? null,
                'groupe_problemes' => $data['groupe_problemes'] ?? [],
                'tension_p1' => $data['tension_p1'] ?? null,
                'tension_p2' => $data['tension_p2'] ?? null,
                'tension_p3' => $data['tension_p3'] ?? null,
                'courant_a' => $data['courant_a'] ?? null,
                'frequence_mesuree_hz' => $data['frequence_mesuree_hz'] ?? null,
                'facteur_puissance' => $data['facteur_puissance'] ?? null,
                'puissance_active_kw' => $data['puissance_active_kw'] ?? null,
                'puissance_apparente_kva' => $data['puissance_apparente_kva'] ?? null,
                'puissance_reactive_kvar' => $data['puissance_reactive_kvar'] ?? null,
                'type_toiture' => $data['type_toiture'] ?? null,
                'orientation' => $data['orientation'] ?? null,
                'inclinaison_deg' => $data['inclinaison_deg'] ?? null,
                'surface_m2' => $data['surface_m2'] ?? null,
                'ombrage' => $data['ombrage'] ?? null,
                'recommandation_type' => $data['recommandation_type'] ?? null,
                'puissance_pv_kwc' => $data['puissance_pv_kwc'] ?? null,
                'onduleur' => $data['onduleur'] ?? null,
                'batteries' => $data['batteries'] ?? null,
                'observations' => $data['observations'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'statut' => $data['statut'] ?? 'soumis',
            ]);

            foreach ($data['charge_items'] ?? [] as $item) {
                $dossier->chargeItems()->create([
                    'equipement' => $item['equipement'],
                    'equipement_autre' => $item['equipement_autre'] ?? null,
                    'quantite' => $item['quantite'] ?? 0,
                    'puissance_w' => $item['puissance_w'] ?? null,
                    'priorite' => $item['priorite'] ?? 'moyenne',
                ]);
            }

            return $dossier;
        });

        $dossier->load(['client', 'agent', 'chargeItems']);

        return response()->json($dossier, 201);
    }

    public function update(StoreDossierRequest $request, Dossier $dossier)
    {
        $this->autoriserAcces($request, $dossier);

        $data = $request->validated();

        DB::transaction(function () use ($data, $dossier) {
            if (! empty($data['client'])) {
                $client = $this->resoudreClient($data['client']);
                $dossier->client_id = $client->id;
            }

            $dossier->fill(collect($data)->except(['client', 'charge_items'])->toArray());
            $dossier->save();

            if (isset($data['charge_items'])) {
                $dossier->chargeItems()->delete();
                foreach ($data['charge_items'] as $item) {
                    $dossier->chargeItems()->create([
                        'equipement' => $item['equipement'],
                        'equipement_autre' => $item['equipement_autre'] ?? null,
                        'quantite' => $item['quantite'] ?? 0,
                        'puissance_w' => $item['puissance_w'] ?? null,
                        'priorite' => $item['priorite'] ?? 'moyenne',
                    ]);
                }
            }
        });

        $dossier->load(['client', 'agent', 'chargeItems', 'documents']);

        return response()->json($dossier);
    }

    private function resoudreClient(array $data): Client
    {
        if (! empty($data['id'])) {
            return Client::findOrFail($data['id']);
        }

        return Client::create([
            'nom' => $data['nom'],
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
        ]);
    }

    private function autoriserAcces(Request $request, Dossier $dossier): void
    {
        $user = $request->user();

        if ($user->isAgent() && $dossier->agent_id !== $user->id) {
            abort(403, "Vous n'avez pas accès à ce dossier.");
        }
    }
}
