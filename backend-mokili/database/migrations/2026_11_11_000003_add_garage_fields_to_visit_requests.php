<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Demandes de rendez-vous garage (prestation + vehicule) dans visit_requests. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visit_requests')) {
            return;
        }
        Schema::table('visit_requests', function (Blueprint $t) {
            if (! Schema::hasColumn('visit_requests', 'service')) {
                $t->string('service', 80)->nullable();
            }
            if (! Schema::hasColumn('visit_requests', 'vehicle')) {
                $t->string('vehicle', 120)->nullable();
            }
            if (! Schema::hasColumn('visit_requests', 'visit_time')) {
                $t->string('visit_time', 5)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('visit_requests')) {
            Schema::table('visit_requests', function (Blueprint $t) {
                foreach (['service', 'vehicle', 'visit_time'] as $c) {
                    if (Schema::hasColumn('visit_requests', $c)) {
                        $t->dropColumn($c);
                    }
                }
            });
        }
    }
};
