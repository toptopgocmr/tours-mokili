<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BILLETTERIE AERIENNE (cahier des charges MOKILI TOUR, sections 11 a 15).
 *
 * Moteur central : recherche -> offres revalidees -> dossier (machine a
 * etats) -> paiement PSP -> reservation fournisseur (PNR) -> emission
 * (billets) -> apres-vente / remboursement. Chaque etape est historisee
 * et chaque operation fournisseur protegee par une cle d'idempotence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('iata_code', 3)->unique();
            $table->string('name');
            $table->string('city');
            $table->string('country_code', 2);
            $table->string('country_name')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('popularity')->default(0);
            $table->timestamps();
            $table->index(['city', 'country_code']);
        });

        // Fournisseurs aeriens / consolidateurs (Suppliers). Les cles API ne
        // sont jamais stockees ici : uniquement dans les variables Railway.
        Schema::create('flight_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('driver');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(10);
            $table->json('settings')->nullable();
            $table->string('connection_status')->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        // Tarifs negocies saisis par l'equipe (fournisseur "manuel" /
        // consolidateur) en attendant la connexion API d'un fournisseur.
        Schema::create('flight_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_supplier_id')->constrained()->cascadeOnDelete();
            $table->string('airline_code', 3);
            $table->string('airline_name');
            $table->string('flight_number')->nullable();
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->string('departure_time', 5);
            $table->string('arrival_time', 5);
            $table->unsignedTinyInteger('arrival_day_offset')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('stops')->default(0);
            $table->string('via')->nullable();
            $table->json('days_of_week')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('cabin')->default('economy');
            $table->string('fare_family')->nullable();
            $table->decimal('base_fare', 12, 2);
            $table->decimal('taxes', 12, 2)->default(0);
            $table->unsignedTinyInteger('child_percent')->default(75);
            $table->unsignedTinyInteger('infant_percent')->default(10);
            $table->string('currency', 3)->default('XAF');
            $table->string('baggage')->nullable();
            $table->string('cabin_baggage')->nullable();
            $table->boolean('refundable')->default(false);
            $table->boolean('changeable')->default(true);
            $table->decimal('change_fee', 12, 2)->nullable();
            $table->decimal('cancel_fee', 12, 2)->nullable();
            $table->text('conditions')->nullable();
            $table->unsignedSmallInteger('seats_available')->nullable();
            $table->unsignedSmallInteger('ticketing_delay_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['origin', 'destination', 'is_active']);
        });

        Schema::create('flight_searches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trip_type')->default('oneway');
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->date('depart_date');
            $table->date('return_date')->nullable();
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('infants')->default(0);
            $table->string('cabin')->default('economy');
            $table->unsignedSmallInteger('results_count')->default(0);
            $table->json('supplier_errors')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('flight_search_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_supplier_id')->constrained();
            $table->string('supplier_offer_id')->nullable();
            $table->string('validating_airline', 3)->nullable();
            $table->string('airline_name')->nullable();
            $table->json('slices');
            $table->json('passengers');
            $table->json('fare_rules')->nullable();
            $table->decimal('supplier_amount', 12, 2);
            $table->decimal('markup_amount', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3);
            $table->timestamp('expires_at');
            $table->timestamp('price_confirmed_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        // Dossier de billetterie : un dossier = une commande client.
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('flight_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flight_supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('DRAFT')->index();
            $table->string('after_sales_status')->nullable();
            $table->string('refund_status')->nullable();
            $table->boolean('needs_attention')->default(false)->index();
            $table->string('attention_reason')->nullable();
            $table->string('pnr')->nullable()->index();
            $table->string('supplier_booking_ref')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('trip_type')->default('oneway');
            $table->string('origin', 3)->nullable();
            $table->string('destination', 3)->nullable();
            $table->date('depart_date')->nullable();
            $table->date('return_date')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('fare_rules')->nullable();
            $table->unsignedTinyInteger('passenger_count')->default(1);
            $table->decimal('supplier_cost', 12, 2)->default(0);
            $table->decimal('markup_amount', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->timestamp('price_confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('ticketing_deadline')->nullable();
            $table->timestamp('ticketed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('last_supplier_check_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->string('type', 3)->default('ADT');
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 2)->nullable();
            $table->string('document_type')->nullable();
            // Chiffre en base (cast "encrypted") - acces restreint et journalise.
            $table->text('document_number')->nullable();
            $table->date('document_expiry')->nullable();
            $table->string('document_country', 2)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_passenger_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('status')->default('issued');
            $table->timestamp('issued_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        // Historique des transitions de la machine a etats.
        Schema::create('flight_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->default('system');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Journal des echanges fournisseur / PSP (sans donnees sensibles).
        Schema::create('flight_supplier_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('flight_supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation');
            $table->string('idempotency_key')->nullable()->index();
            $table->boolean('success')->default(false);
            $table->string('outcome')->nullable();
            $table->string('external_ref')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Cle d'idempotence par operation (paiement, reservation, emission).
        Schema::create('flight_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('operation');
            $table->foreignId('flight_booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('state')->default('started');
            $table->json('result')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamps();
        });

        // Demandes apres-vente : annulation, modification, remboursement.
        Schema::create('flight_after_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->text('reason');
            $table->json('attachments')->nullable();
            $table->string('status')->default('submitted');
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->decimal('supplier_fee', 12, 2)->nullable();
            $table->decimal('psp_fee', 12, 2)->nullable();
            $table->decimal('penalty', 12, 2)->nullable();
            $table->decimal('refundable_amount', 12, 2)->nullable();
            $table->string('estimated_delay')->nullable();
            $table->string('refund_status')->nullable();
            $table->string('refund_reference')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Journal des actions administratives et consultations sensibles.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('flight_after_sales');
        Schema::dropIfExists('flight_idempotency_keys');
        Schema::dropIfExists('flight_supplier_logs');
        Schema::dropIfExists('flight_status_histories');
        Schema::dropIfExists('flight_tickets');
        Schema::dropIfExists('flight_passengers');
        Schema::dropIfExists('flight_bookings');
        Schema::dropIfExists('flight_offers');
        Schema::dropIfExists('flight_searches');
        Schema::dropIfExists('flight_fares');
        Schema::dropIfExists('flight_suppliers');
        Schema::dropIfExists('airports');
    }
};
