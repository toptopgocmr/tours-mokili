<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Adds refund tracking so the admin "Paiements" page (see
// Admin\PaymentController) can mark a payment as refunded with a
// reason, separate from the original paid/failed outcome. The status
// enums are altered via raw SQL on MySQL (Railway's driver); on other
// drivers (e.g. sqlite in local tests) the enum constraint isn't
// widened, but the app never writes 'refunded' there, so this only
// matters in production, where the driver is always mysql.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','processing','paid','failed','refunded') DEFAULT 'pending'");
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('pending','awaiting_payment','confirmed','cancelled','completed','refunded') DEFAULT 'pending'");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->text('refund_reason')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'refund_reason']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','processing','paid','failed') DEFAULT 'pending'");
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('pending','awaiting_payment','confirmed','cancelled','completed') DEFAULT 'pending'");
        }
    }
};
