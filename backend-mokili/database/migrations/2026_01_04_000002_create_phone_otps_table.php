<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Short-lived SMS verification codes for "Continuer avec mon numero" (see
// PhoneOtpController). A row is consumed (verified_at set) or expires;
// old rows are cheap to keep for abuse auditing and aren't cleaned up
// automatically yet.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('phone_otps')) {
            return;
        }

        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
    }
};
