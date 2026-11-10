<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vente de vehicules facon Spoticar : galerie photos, version / finition, garantie, nombre de vues. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            if (! Schema::hasColumn('vehicles', 'gallery')) {
                $t->json('gallery')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'version')) {
                $t->string('version', 120)->nullable(); // ex. "2.5 Hybrid Dynamic"
            }
            if (! Schema::hasColumn('vehicles', 'warranty_months')) {
                $t->unsignedSmallInteger('warranty_months')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'views')) {
                $t->unsignedInteger('views')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            foreach (['gallery', 'version', 'warranty_months', 'views'] as $c) {
                if (Schema::hasColumn('vehicles', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
