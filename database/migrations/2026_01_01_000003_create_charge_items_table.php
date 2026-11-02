<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->enum('equipement', ['splits', 'ordinateurs', 'eclairage', 'serveurs', 'pompes', 'imprimantes', 'refrigerateurs', 'autres']);
            $table->string('equipement_autre')->nullable(); // libellé si "autres"
            $table->integer('quantite')->default(0);
            $table->decimal('puissance_w', 10, 2)->nullable();
            $table->enum('priorite', ['haute', 'moyenne', 'basse'])->default('moyenne');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_items');
    }
};
