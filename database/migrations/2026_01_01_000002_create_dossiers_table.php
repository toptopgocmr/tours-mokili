<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('numero_dossier')->unique(); // SSEA-2026-0001

            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();

            // 1. Identification du projet
            $table->date('date_intervention');
            $table->time('heure_intervention')->nullable();
            $table->string('responsable_technique')->nullable();

            // 2. Nature du site
            $table->enum('type_site', ['maison', 'appartement', 'bureaux', 'coworking', 'commerce', 'industrie', 'hotel', 'hopital'])->nullable();
            $table->enum('niveaux', ['rdc', 'r1', 'r2', 'r3_plus'])->nullable();

            // 3. Objectif du client (choix multiples)
            $table->json('objectifs')->nullable();
            // valeurs possibles: reduire_facture, continuite_service, remplacer_groupe, soulager_groupe, hybride, autonome

            // 4. Réseau électrique
            $table->enum('type_reseau', ['monophase', 'triphase'])->nullable();
            $table->decimal('tension_v', 8, 2)->nullable();
            $table->decimal('frequence_hz', 6, 2)->nullable();
            $table->decimal('puissance_souscrite_kva', 8, 2)->nullable();
            $table->boolean('facture_e2c')->nullable();

            // 5. Groupe électrogène
            $table->string('groupe_marque')->nullable();
            $table->decimal('groupe_puissance_kva', 8, 2)->nullable();
            $table->string('groupe_annee')->nullable();
            $table->json('groupe_problemes')->nullable();
            // valeurs possibles: sous_dimensionne, surcharge, forte_consommation, pannes

            // 7. Mesures électriques
            $table->decimal('tension_p1', 8, 2)->nullable();
            $table->decimal('tension_p2', 8, 2)->nullable();
            $table->decimal('tension_p3', 8, 2)->nullable();
            $table->decimal('courant_a', 8, 2)->nullable();
            $table->decimal('frequence_mesuree_hz', 6, 2)->nullable();
            $table->decimal('facteur_puissance', 4, 2)->nullable();
            $table->decimal('puissance_active_kw', 8, 2)->nullable();
            $table->decimal('puissance_apparente_kva', 8, 2)->nullable();
            $table->decimal('puissance_reactive_kvar', 8, 2)->nullable();

            // 8. Analyse de la toiture
            $table->enum('type_toiture', ['tole', 'beton', 'terrasse', 'bac_acier'])->nullable();
            $table->string('orientation')->nullable();
            $table->decimal('inclinaison_deg', 5, 2)->nullable();
            $table->decimal('surface_m2', 8, 2)->nullable();
            $table->enum('ombrage', ['aucun', 'faible', 'important'])->nullable();

            // 10. Recommandation préliminaire
            $table->enum('recommandation_type', ['hybride', 'autonome', 'reseau'])->nullable();
            $table->decimal('puissance_pv_kwc', 8, 2)->nullable();
            $table->string('onduleur')->nullable();
            $table->string('batteries')->nullable();

            // 11. Observations & signatures
            $table->text('observations')->nullable();
            $table->string('signature_client_path')->nullable();
            $table->string('signature_ssea_path')->nullable();

            // Géolocalisation optionnelle du site
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('statut', ['brouillon', 'soumis', 'valide', 'devis_envoye', 'facture_envoyee'])->default('brouillon');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
