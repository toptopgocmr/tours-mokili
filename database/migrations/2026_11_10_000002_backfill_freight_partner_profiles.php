<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Les transporteurs deja en ligne avant le module de validation sont consideres comme approuves
// (leurs offres restent visibles) ; le staff peut les suspendre ensuite.
return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->whereIn('id', DB::table('freight_offers')->whereNotNull('carrier_id')->pluck('carrier_id'))->get();

        foreach ($users as $u) {
            if (DB::table('freight_partner_profiles')->where('user_id', $u->id)->exists()) {
                continue;
            }
            DB::table('freight_partner_profiles')->insert([
                'user_id' => $u->id, 'company_name' => $u->name, 'contact_email' => $u->email,
                'status' => 'approved', 'review_note' => 'Partenaire existant (reprise automatique).',
                'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
