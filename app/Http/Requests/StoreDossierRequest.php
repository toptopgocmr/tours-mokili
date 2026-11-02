<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Client
            'client.id' => ['nullable', 'exists:clients,id'],
            'client.nom' => ['required_without:client.id', 'string', 'max:255'],
            'client.telephone' => ['nullable', 'string', 'max:50'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.adresse' => ['nullable', 'string'],

            // 1. Identification du projet
            'date_intervention' => ['required', 'date'],
            'heure_intervention' => ['nullable', 'date_format:H:i'],
            'responsable_technique' => ['nullable', 'string', 'max:255'],

            // 2. Nature du site
            'type_site' => ['nullable', 'in:maison,appartement,bureaux,coworking,commerce,industrie,hotel,hopital'],
            'niveaux' => ['nullable', 'in:rdc,r1,r2,r3_plus'],

            // 3. Objectif du client
            'objectifs' => ['nullable', 'array'],
            'objectifs.*' => ['in:reduire_facture,continuite_service,remplacer_groupe,soulager_groupe,hybride,autonome'],

            // 4. Réseau électrique
            'type_reseau' => ['nullable', 'in:monophase,triphase'],
            'tension_v' => ['nullable', 'numeric'],
            'frequence_hz' => ['nullable', 'numeric'],
            'puissance_souscrite_kva' => ['nullable', 'numeric'],
            'facture_e2c' => ['nullable', 'boolean'],

            // 5. Groupe électrogène
            'groupe_marque' => ['nullable', 'string', 'max:255'],
            'groupe_puissance_kva' => ['nullable', 'numeric'],
            'groupe_annee' => ['nullable', 'string', 'max:10'],
            'groupe_problemes' => ['nullable', 'array'],
            'groupe_problemes.*' => ['in:sous_dimensionne,surcharge,forte_consommation,pannes'],

            // 6. Inventaire des charges
            'charge_items' => ['nullable', 'array'],
            'charge_items.*.equipement' => ['required_with:charge_items', 'in:splits,ordinateurs,eclairage,serveurs,pompes,imprimantes,refrigerateurs,autres'],
            'charge_items.*.equipement_autre' => ['nullable', 'string', 'max:255'],
            'charge_items.*.quantite' => ['nullable', 'integer', 'min:0'],
            'charge_items.*.puissance_w' => ['nullable', 'numeric', 'min:0'],
            'charge_items.*.priorite' => ['nullable', 'in:haute,moyenne,basse'],

            // 7. Mesures électriques
            'tension_p1' => ['nullable', 'numeric'],
            'tension_p2' => ['nullable', 'numeric'],
            'tension_p3' => ['nullable', 'numeric'],
            'courant_a' => ['nullable', 'numeric'],
            'frequence_mesuree_hz' => ['nullable', 'numeric'],
            'facteur_puissance' => ['nullable', 'numeric'],
            'puissance_active_kw' => ['nullable', 'numeric'],
            'puissance_apparente_kva' => ['nullable', 'numeric'],
            'puissance_reactive_kvar' => ['nullable', 'numeric'],

            // 8. Analyse de la toiture
            'type_toiture' => ['nullable', 'in:tole,beton,terrasse,bac_acier'],
            'orientation' => ['nullable', 'string', 'max:100'],
            'inclinaison_deg' => ['nullable', 'numeric'],
            'surface_m2' => ['nullable', 'numeric'],
            'ombrage' => ['nullable', 'in:aucun,faible,important'],

            // 10. Recommandation préliminaire
            'recommandation_type' => ['nullable', 'in:hybride,autonome,reseau'],
            'puissance_pv_kwc' => ['nullable', 'numeric'],
            'onduleur' => ['nullable', 'string', 'max:255'],
            'batteries' => ['nullable', 'string', 'max:255'],

            // 11. Observations
            'observations' => ['nullable', 'string'],

            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'statut' => ['nullable', 'in:brouillon,soumis,valide,devis_envoye,facture_envoyee'],
        ];
    }
}
