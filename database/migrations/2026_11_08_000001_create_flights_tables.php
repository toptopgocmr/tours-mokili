<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * VOYAGE / VOLS - billetterie aerienne (cahier des charges Mokili Tour,
 * section 13 "Modele de donnees minimal").
 *
 * Le paiement reste porte par la table generique `bookings` + `payments`
 * (PaymentService) : chaque dossier de vol (flight_orders) possede UNE
 * reservation generique (booking_id) qui sert d'enveloppe de paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('iata', 3)->unique();
            $table->string('name');
            $table->string('city');
            $table->string('country', 2);
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index('city');
        });

        Schema::create('flight_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // fake, amadeus, ...
            $table->string('name');
            $table->string('driver');                 // cle du connecteur (config/flights.php)
            $table->boolean('is_active')->default(true);
            $table->string('connection_status')->default('unknown'); // ok, degraded, down, unknown
            $table->timestamp('last_checked_at')->nullable();
            $table->json('settings')->nullable();     // parametres NON secrets (les cles restent dans l'environnement)
            $table->timestamps();
        });

        Schema::create('flight_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->date('depart_date');
            $table->date('return_date')->nullable();
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('infants')->default(0);
            $table->string('cabin')->default('economy');
            $table->unsignedInteger('results_count')->default(0);
            $table->json('supplier_errors')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_search_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_supplier_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');             // identifiant de l'offre chez le fournisseur
            $table->json('itinerary');                 // segments aller / retour
            $table->string('airline_code', 3)->nullable();
            $table->string('airline_name')->nullable();
            $table->decimal('price_total', 12, 2);     // prix client TTC (commission Mokili incluse)
            $table->decimal('supplier_cost', 12, 2);   // cout fournisseur
            $table->string('currency', 3)->default('XAF');
            $table->json('fare_rules')->nullable();    // bagages, modification, remboursement
            $table->timestamp('expires_at');
            $table->timestamp('price_confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['flight_search_id', 'price_total']);
        });

        Schema::create('flight_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();     // MKT-VOL-XXXXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('agency_id')->nullable()->index(); // espace partenaire (phase 6)
            $table->foreignId('flight_offer_id')->constrained()->restrictOnDelete();
            $table->foreignId('flight_supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete(); // enveloppe de paiement
            $table->string('status')->default('DRAFT')->index();
            $table->string('pnr')->nullable()->index();
            $table->string('supplier_order_id')->nullable();
            $table->decimal('price_total', 12, 2);
            $table->decimal('supplier_cost', 12, 2);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            // Annexe : paiement confirme mais reservation/emission en echec = anomalie.
            $table->boolean('is_anomaly')->default(false)->index();
            $table->text('failure_reason')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('ticketed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('adult');  // adult, child, infant
            $table->string('title', 10)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('gender', 1)->nullable();
            $table->string('nationality', 2)->nullable();
            $table->text('passport_number')->nullable();   // chiffre (cast encrypted)
            $table->date('passport_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_passenger_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('status')->default('issued'); // issued, voided, refunded
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        // Machine a etats : chaque transition est historisee.
        Schema::create('flight_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor')->default('system'); // system, user:ID, admin:ID, webhook
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // Idempotence : une ligne par operation externe (paiement, reservation, emission).
        Schema::create('flight_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->string('type');                    // payment, booking, ticketing, cancel
            $table->string('idempotency_key')->unique();
            $table->string('status')->default('started'); // started, succeeded, failed, unknown
            $table->string('external_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->index(['flight_order_id', 'type']);
        });

        Schema::create('flight_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason_code');             // customer_request, ticketing_failed, supplier_cancel, duplicate_payment...
            $table->text('reason')->nullable();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('supplier_fee', 12, 2)->default(0);
            $table->decimal('psp_fee', 12, 2)->default(0);
            $table->decimal('penalty', 12, 2)->default(0);
            $table->decimal('refundable_amount', 12, 2);
            $table->string('mode')->default('manual'); // auto, manual
            $table->string('status')->default('requested'); // requested, approved, refunded, rejected
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->decimal('rate_percent', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('status')->default('pending'); // pending, earned, reversed, settled
            $table->unsignedBigInteger('settlement_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('flight_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->string('status')->default('draft'); // draft, validated, paid
            $table->timestamps();
        });

        Schema::create('flight_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('email'); // email, sms, app
            $table->string('event');                     // payment_confirmed, booked, ticketed, failed, cancelled, refunded
            $table->text('message');
            $table->string('result')->default('queued'); // queued, sent, failed
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Journal d'audit : ajout seul (aucune route ne modifie ni ne supprime).
        Schema::create('flight_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('actor')->default('system');
            $table->string('ip', 45)->nullable();
            $table->json('context')->nullable();       // jamais de donnees sensibles (passeport, carte)
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
        });
    }

    public function down(): void
    {
        foreach ([
            'flight_audit_logs', 'flight_notifications', 'flight_settlements', 'flight_commissions',
            'flight_refunds', 'flight_operations', 'flight_status_history', 'flight_tickets',
            'flight_passengers', 'flight_orders', 'flight_offers', 'flight_searches',
            'flight_suppliers', 'airports',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
