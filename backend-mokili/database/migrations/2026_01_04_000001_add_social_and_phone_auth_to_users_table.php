<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Supports "Continuer avec Google / Facebook / Instagram" (provider +
// provider_id identify the social account) and phone/OTP sign-in
// (phone_verified_at marks a phone confirmed via SMS code, see
// PhoneOtpController). Guarded with hasColumn()/hasTable() so a deploy
// killed mid-migration (see 2026_01_02_000001_create_freight_offers_table)
// can safely retry instead of crashing on "column already exists".
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Explicit (short) lengths here: the default VARCHAR(191) from
            // Schema::defaultStringLength(191) in AppServiceProvider is
            // fine for a single indexed column, but this table also puts
            // a COMPOSITE unique index across provider+provider_id below,
            // and 191+191 chars (utf8mb4, 4 bytes/char) blows past MySQL's
            // key-length limit ("1071 Specified key was too long"). These
            // values are never more than a few dozen characters in
            // practice (e.g. 'google', a numeric provider id).
            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider', 20)->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id', 100)->nullable()->after('provider');
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('provider_id');
            }
        });

        // A deploy before this fix already created provider/provider_id
        // as the (too-wide) default VARCHAR(191) - the hasColumn() guards
        // above skip re-adding them, so they'd stay wide forever and the
        // unique index below would keep failing with the same "1071
        // Specified key was too long" error. MODIFY unconditionally
        // (safe to re-run) instead of Schema::table()->change(), which
        // needs doctrine/dbal - not installed, and not addable here (see
        // SocialOAuthService docblock on why composer.lock can't be
        // regenerated in this environment).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY provider VARCHAR(20) NULL');
            DB::statement('ALTER TABLE users MODIFY provider_id VARCHAR(100) NULL');
        }

        if (! $this->hasUniqueIndex()) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['provider', 'provider_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id', 'phone_verified_at']);
        });
    }

    private function hasUniqueIndex(): bool
    {
        $indexes = Schema::getIndexes('users');

        foreach ($indexes as $index) {
            if ($index['columns'] === ['provider', 'provider_id']) {
                return true;
            }
        }

        return false;
    }
};
