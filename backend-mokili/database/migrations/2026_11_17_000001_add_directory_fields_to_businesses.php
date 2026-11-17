<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ANNUAIRE : inscription gratuite des entreprises depuis /annuaire/inscription.
// submitted_at = date de la demande ; la fiche reste masquee (is_active = false)
// jusqu'a validation dans /admin/partenaires.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (! Schema::hasColumn('businesses', 'contact_person')) {
                $table->string('contact_person')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'contact_person']);
        });
    }
};
