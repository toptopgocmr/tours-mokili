<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Upload d'une photo/document lié à un dossier (section 9 de la fiche).
     */
    public function store(Request $request, Dossier $dossier)
    {
        $user = $request->user();
        if ($user->isAgent() && $dossier->agent_id !== $user->id) {
            abort(403, "Vous n'avez pas accès à ce dossier.");
        }

        $data = $request->validate([
            'type' => ['required', 'in:facture_e2c,tableau,compteur,groupe,plaques,toiture,gps,autre'],
            'fichier' => ['required', 'file', 'image', 'max:10240'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $fichier = $request->file('fichier');
        $nom = Str::uuid().'.'.$fichier->getClientOriginalExtension();
        $chemin = $fichier->storeAs('dossiers/'.$dossier->id, $nom, 'public');

        $document = $dossier->documents()->create([
            'type' => $data['type'],
            'fichier_path' => $chemin,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return response()->json($document, 201);
    }

    public function destroy(Request $request, Dossier $dossier, \App\Models\Document $document)
    {
        $user = $request->user();
        if ($user->isAgent() && $dossier->agent_id !== $user->id) {
            abort(403, "Vous n'avez pas accès à ce dossier.");
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($document->fichier_path);
        $document->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }
}
