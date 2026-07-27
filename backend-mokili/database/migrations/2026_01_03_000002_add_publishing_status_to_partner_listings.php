<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds a moderation workflow (draft -> pending -> published/rejected) on
// top of the existing `is_active` toggle for every partner-ownable
// listing table. `is_active` stays the partner's own show/hide switch;
// `status` is admin-controlled and gates public visibility (see each
// model's scopeActive()). Voyage is excluded - it's centrally managed
// by admin/agent staff, not a partner listing.
return new class extends Migration
{
    private array $tables = ['lodging_listings', 'vehicles', 'events', 'marketplace_products', 'freight_offers'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('status')->default('draft')->after('is_active');
                $blueprint->text('rejection_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['status', 'rejection_reason']);
            });
        }
    }
};
