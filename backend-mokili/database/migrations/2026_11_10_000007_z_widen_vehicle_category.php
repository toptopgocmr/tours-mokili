<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La colonne "category" des vehicules etait une liste fermee (citadine,
 * berline, suv, utilitaire, luxe) : les nouvelles carrosseries (4x4, pick-up,
 * monospace, coupe) etaient refusees par MySQL. Elle devient un texte libre,
 * valide par l'application (config/catalogue.php > voiture.categories).
 * Doit passer avant le catalogue d'exemple (000008).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->string('category', 30)->default('berline')->change();
            $t->string('transmission', 20)->default('manuelle')->change();
        });
    }

    public function down(): void
    {
        // Retour a la liste fermee impossible sans perte : on garde le texte.
    }
};
