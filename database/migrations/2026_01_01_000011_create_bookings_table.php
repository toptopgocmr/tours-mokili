<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Generic booking table shared by every service module via a
// polymorphic relation (bookable_type/bookable_id), so Logement,
// Voiture, Divertissement, Marketplace and Fret can plug into the
// same booking + payment pipeline as Voyage without a schema change.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // e.g. MKT-VOY-000123
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bookable_type'); // App\Models\Voyage\TravelOffer, etc.
            $table->unsignedBigInteger('bookable_id');
            $table->unsignedInteger('quantity')->default(1); // passengers/nights/units...
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['pending', 'awaiting_payment', 'confirmed', 'cancelled', 'completed'])
                ->default('pending');
            $table->json('meta')->nullable(); // module-specific extra data
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bookable_type', 'bookable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
