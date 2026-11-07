<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FRET & LOGISTIQUE - marketplace de partenaires verifies (cahier de cadrage
 * novembre 2026) : candidature partenaire, devis, commandes, suivi par
 * evenements, reclamations, commissions, documents prives, audit.
 *
 * Le paiement reste porte par `bookings` + `payments` (PaymentService) :
 * chaque commande (freight_orders) possede une reservation generique qui sert
 * d'enveloppe de paiement. Paiement confirme <> reservation confirmee <> livre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Partenaires (candidature + validation) ---
        Schema::create('freight_partner_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('company_name');
            $t->string('trade_name')->nullable();
            $t->text('description')->nullable();
            $t->string('contact_email')->nullable();
            $t->string('contact_phone')->nullable();
            $t->string('address')->nullable();
            $t->string('country', 2)->nullable();
            $t->json('zones')->nullable();                       // zones desservies
            $t->json('modes')->nullable();                       // air, mer, route
            $t->string('logo')->nullable();
            // pending (en verification), needs_correction, approved, rejected, suspended
            $t->string('status')->default('pending')->index();
            $t->text('review_note')->nullable();                 // motif de refus / correction / suspension
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->decimal('commission_percent', 5, 2)->nullable(); // surcharge par partenaire
            $t->timestamps();
        });

        Schema::create('freight_documents', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('documentable');                  // partner profile, quote request, order, shipment
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // depose par
            $t->string('type');                                  // kbis, licence, assurance, facture_commerciale, colisage, preuve_livraison...
            $t->string('original_name');
            $t->string('path');                                  // disque PRIVE
            $t->string('visibility')->default('parties');        // parties (client+partenaire+staff), staff
            $t->date('expires_at')->nullable();
            $t->timestamps();
        });

        // --- Offres : colonnes du cadrage (tarif instantane / sur devis, validite, inclusions) ---
        Schema::table('freight_offers', function (Blueprint $t) {
            $t->string('service_type')->default('colis')->after('mode');   // colis, marchandises, vehicule, conteneur, groupage
            $t->string('pricing_mode')->default('instant')->after('service_type'); // instant | quote
            $t->boolean('price_is_firm')->default(false)->after('pricing_mode');   // estimation ou prix ferme
            $t->string('unit')->default('kg')->after('price_per_kg');              // kg, m3, tonne, forfait
            $t->decimal('min_price', 12, 2)->nullable()->after('unit');
            $t->date('valid_until')->nullable()->after('min_price');
            $t->unsignedSmallInteger('transit_days_min')->nullable()->after('valid_until');
            $t->unsignedSmallInteger('transit_days_max')->nullable()->after('transit_days_min');
            $t->text('inclusions')->nullable()->after('description');
            $t->text('exclusions')->nullable()->after('inclusions');
            $t->text('restrictions')->nullable()->after('exclusions');             // marchandises refusees / reglementees
            $t->decimal('promo_percent', 5, 2)->nullable()->after('min_price');
            $t->date('promo_until')->nullable()->after('promo_percent');
        });

        // --- Demandes de devis ---
        Schema::create('freight_quote_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();                   // FRT-DEM-XXXXXX
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('freight_offer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('partner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('mode');                                  // air, mer, route
            $t->string('origin_country', 2);
            $t->string('origin_city');
            $t->string('destination_country', 2);
            $t->string('destination_city');
            $t->string('cargo_type')->default('colis');          // colis, marchandises, vehicule, conteneur
            $t->text('description');
            $t->decimal('weight_kg', 10, 2)->nullable();
            $t->decimal('volume_m3', 10, 3)->nullable();
            $t->string('dimensions')->nullable();
            $t->unsignedInteger('packages')->nullable();
            $t->string('load_type')->nullable();                 // FCL | LCL (maritime)
            $t->string('container_type')->nullable();            // 20GP, 40GP, 40HC...
            $t->date('ready_date')->nullable();
            $t->date('desired_pickup_date')->nullable();
            $t->date('desired_delivery_date')->nullable();
            $t->json('services')->nullable();                    // pickup, packing, insurance, customs, storage, delivery
            $t->decimal('declared_value', 14, 2)->nullable();
            $t->boolean('dangerous_goods')->default(false);
            $t->string('status')->default('DEMANDE_ENVOYEE')->index();
            $t->timestamp('partner_ack_at')->nullable();         // accuse de reception du partenaire
            $t->timestamps();
        });

        // --- Devis partenaire (versionnes) ---
        Schema::create('freight_quotes', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();                   // FRT-DEV-XXXXXX
            $t->foreignId('quote_request_id')->constrained('freight_quote_requests')->cascadeOnDelete();
            $t->foreignId('partner_user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedSmallInteger('version')->default(1);
            // sent, accepted, refused, expired, superseded (remplace par une version plus recente)
            $t->string('status')->default('sent')->index();
            $t->string('currency', 3)->default('XAF');
            $t->json('lines');                                   // [{label, amount, kind: included|excluded|estimated|third_party}]
            $t->decimal('total_amount', 14, 2);
            $t->boolean('is_firm')->default(true);               // prix ferme ou estimation
            $t->date('valid_until');
            $t->unsignedSmallInteger('transit_days')->nullable();
            $t->text('conditions')->nullable();
            $t->text('exclusions')->nullable();                  // taxes, douane, stockage non inclus
            $t->timestamp('accepted_at')->nullable();
            $t->json('accepted_snapshot')->nullable();           // copie figee au moment de l'acceptation
            $t->timestamps();
        });

        // --- Commandes (reservations) ---
        Schema::create('freight_orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();                   // FRT-RES-XXXXXX
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('partner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('freight_offer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('quote_id')->nullable()->constrained('freight_quotes')->nullOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();   // enveloppe de paiement
            $t->foreignId('shipment_id')->nullable()->constrained('freight_shipments')->nullOnDelete();
            // DEVIS_ACCEPTE, PAIEMENT_EN_ATTENTE, PAIEMENT_CONFIRME, RESERVATION_CONFIRMEE, ANNULE, LITIGE
            $t->string('status')->default('DEVIS_ACCEPTE')->index();
            $t->string('currency', 3)->default('XAF');
            $t->decimal('total_amount', 14, 2);
            $t->decimal('commission_percent', 5, 2)->default(0);
            $t->decimal('commission_amount', 14, 2)->default(0);
            $t->decimal('partner_amount', 14, 2)->default(0);
            $t->json('frozen_terms');                            // conditions acceptees, figees
            $t->text('pickup_instructions')->nullable();         // modalites de remise / enlevement
            $t->text('cancel_reason')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('payment_confirmed_at')->nullable();
            $t->timestamp('confirmed_at')->nullable();           // confirmation partenaire
            $t->timestamp('refunded_at')->nullable();
            $t->string('settlement_ref')->nullable()->index();   // reversement partenaire
            $t->timestamp('settled_at')->nullable();
            $t->timestamps();
        });

        // --- Suivi : evenements ajoutes seulement (correction = nouvel evenement) ---
        Schema::table('freight_shipments', function (Blueprint $t) {
            $t->string('status')->default('enregistre')->change();          // enum -> string (statuts du cadrage)
            $t->foreignId('partner_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $t->foreignId('freight_order_id')->nullable()->after('partner_user_id');
            $t->timestamp('proof_at')->nullable();
        });

        Schema::create('freight_tracking_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained('freight_shipments')->cascadeOnDelete();
            $t->string('status');                                // PRISE_EN_CHARGE, EN_TRANSIT, ...
            $t->string('location')->nullable();
            $t->timestamp('occurred_at');
            $t->text('comment')->nullable();
            $t->string('source')->default('partner');            // partner:ID, admin:ID, system, api
            $t->foreignId('document_id')->nullable()->constrained('freight_documents')->nullOnDelete();
            $t->foreignId('corrects_event_id')->nullable();      // correction tracee
            $t->timestamps();

            $t->index(['shipment_id', 'occurred_at']);
        });

        // --- Reclamations ---
        Schema::create('freight_claims', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();                   // FRT-REC-XXXXXX
            $t->foreignId('freight_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('reason');                                // perte, retard, dommage, facturation, autre
            $t->text('description');
            // open, in_review, documents_requested, resolved, rejected
            $t->string('status')->default('open')->index();
            $t->text('resolution')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('freight_claim_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('freight_claims')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('author_role');                           // client, partner, staff
            $t->text('body');
            $t->timestamps();
        });

        // --- Notifications et journal d'audit (ajout seul) ---
        Schema::create('freight_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->string('subject_ref')->nullable();
            $t->text('message');
            $t->string('channel')->default('app');
            $t->string('result')->default('sent');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('freight_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('action')->index();
            $t->string('actor')->default('system');              // user:ID, admin:ID, system
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('reason')->nullable();
            $t->json('context')->nullable();                     // jamais de donnees sensibles
            $t->timestamp('created_at')->useCurrent();

            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['freight_audit_logs', 'freight_notifications', 'freight_claim_messages', 'freight_claims', 'freight_tracking_events',
            'freight_orders', 'freight_quotes', 'freight_quote_requests', 'freight_documents', 'freight_partner_profiles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('freight_shipments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('partner_user_id');
            $t->dropColumn(['freight_order_id', 'proof_at']);
        });

        Schema::table('freight_offers', function (Blueprint $t) {
            $t->dropColumn(['service_type', 'pricing_mode', 'price_is_firm', 'unit', 'min_price', 'valid_until', 'transit_days_min',
                'transit_days_max', 'inclusions', 'exclusions', 'restrictions', 'promo_percent', 'promo_until']);
        });
    }
};
