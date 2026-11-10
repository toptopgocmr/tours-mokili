<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vente de vehicules : couleur, portes, etat, premiere main, moteur, equipements. */
return new class extends Migration
{
    private const COLUMNS = ['color', 'doors', 'condition', 'first_owner', 'engine', 'options'];

    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            if (! Schema::hasColumn('vehicles', 'color')) {
                $t->string('color', 40)->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'doors')) {
                $t->unsignedTinyInteger('doors')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'condition')) {
                $t->string('condition', 20)->nullable(); // neuf, occasion, reconditionne
            }
            if (! Schema::hasColumn('vehicles', 'first_owner')) {
                $t->boolean('first_owner')->default(false);
            }
            if (! Schema::hasColumn('vehicles', 'engine')) {
                $t->string('engine', 60)->nullable(); // ex. 2.0 L, V6
            }
            if (! Schema::hasColumn('vehicles', 'options')) {
                $t->json('options')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            foreach (self::COLUMNS as $c) {
                if (Schema::hasColumn('vehicles', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
