<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero_facture')->unique(); // FAC-2026-0001
            $table->foreignId('devis_id')->nullable()->constrained('devis')->nullOnDelete();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('date_facture');
            $table->date('date_echeance')->nullable();
            $table->enum('statut', ['brouillon', 'envoyee', 'payee_partiel', 'payee', 'annulee'])->default('brouillon');
            $table->decimal('sous_total', 14, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(18);
            $table->decimal('montant_tva', 14, 2)->default(0);
            $table->decimal('total_ttc', 14, 2)->default(0);
            $table->decimal('montant_paye', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('facture_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->string('designation');
            $table->text('description')->nullable();
            $table->decimal('quantite', 10, 2)->default(1);
            $table->decimal('prix_unitaire', 14, 2)->default(0);
            $table->decimal('montant_ht', 14, 2)->default(0);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_lignes');
        Schema::dropIfExists('factures');
    }
};
