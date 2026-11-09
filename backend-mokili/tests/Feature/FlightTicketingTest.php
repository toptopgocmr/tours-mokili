<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightFare;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Models\Flights\FlightSupplier;
use App\Models\Flights\FlightSupplierLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\Flights\FlightFulfillmentService;
use App\Services\Flights\FlightStateMachine;
use App\Services\Flights\FlightStatus;
use App\Services\Flights\InvalidFlightTransition;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Scenarios de recette du cahier des charges "Billetterie aerienne" (section 19). */
class FlightTicketingTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['role' => 'client', 'phone' => '+242060000000']);
    }

    // ---------------------------------------------------------------- helpers

    protected function manualFare(array $attrs = []): FlightFare
    {
        return FlightFare::create($attrs + [
            'flight_supplier_id' => FlightSupplier::where('code', 'manuel')->value('id'),
            'airline_code' => 'ET', 'airline_name' => 'Ethiopian Airlines', 'flight_number' => 'ET 834',
            'origin' => 'BZV', 'destination' => 'CDG', 'departure_time' => '23:10', 'arrival_time' => '06:40',
            'arrival_day_offset' => 1, 'duration_minutes' => 690, 'stops' => 1, 'via' => 'ADD',
            'cabin' => 'economy', 'base_fare' => 400000, 'taxes' => 150000, 'currency' => 'XAF',
            'baggage' => '2 x 23 kg', 'refundable' => true, 'cancel_fee' => 50000, 'ticketing_delay_hours' => 24,
        ]);
    }

    protected function searchParams(array $overrides = []): array
    {
        return $overrides + [
            'trip_type' => 'oneway', 'origin' => 'BZV', 'destination' => 'CDG',
            'depart_date' => now()->addDays(20)->toDateString(), 'adults' => 1, 'children' => 0, 'infants' => 0, 'cabin' => 'economy',
        ];
    }

    protected function search(array $overrides = []): FlightSearch
    {
        $this->get('/voyage/vols/recherche?'.http_build_query($this->searchParams($overrides)))->assertRedirect();

        return FlightSearch::latest('id')->firstOrFail();
    }

    protected function passengerPayload(FlightOffer $offer, array $types = ['ADT']): array
    {
        return [
            'contact_name' => 'Jean Mabiala', 'contact_email' => 'jean@example.com', 'contact_phone' => '+242060000000',
            'expected_total' => $offer->total_amount, 'accept_conditions' => true,
            'passengers' => array_map(fn ($t) => [
                'type' => $t, 'title' => $t === 'ADT' ? 'M.' : 'Enfant', 'first_name' => 'Jean', 'last_name' => 'Mabiala',
                'birth_date' => match ($t) { 'INF' => now()->subMonths(10)->toDateString(), 'CHD' => now()->subYears(6)->toDateString(), default => '1990-04-12' },
                'nationality' => 'CG', 'document_type' => 'passport', 'document_number' => 'OA1234567',
                'document_expiry' => now()->addYears(3)->toDateString(), 'document_country' => 'CG',
            ], $types),
        ];
    }

    protected function bookDossier(FlightOffer $offer, array $types = ['ADT']): FlightBooking
    {
        $this->actingAs($this->client)
            ->post("/voyage/vols/offres/{$offer->uuid}/reserver", $this->passengerPayload($offer, $types))
            ->assertRedirect();

        return FlightBooking::latest('id')->firstOrFail();
    }

    protected function pay(FlightBooking $flight, bool $success = true): Payment
    {
        $booking = $flight->booking;
        app(\App\Services\Flights\FlightBookingService::class)->beforePayment($booking->fresh());
        $payment = Payment::create([
            'booking_id' => $booking->id, 'user_id' => $flight->user_id, 'provider' => 'maishapay',
            'method' => 'mobile_money', 'phone' => '242060000000', 'country_code' => 'CG',
            'amount' => $booking->total_amount, 'currency' => $booking->currency, 'status' => 'pending',
        ]);
        app(PaymentService::class)->markPayment($payment, $success, $success ? null : 'Solde insuffisant');

        return $payment->fresh();
    }

    protected function useSandbox(?string $behaviour = null): void
    {
        config(['flights.sandbox_enabled' => true, 'flights.sandbox_behaviour' => $behaviour]);
        FlightSupplier::where('code', 'manuel')->update(['is_active' => false]);
    }

    // ------------------------------------------------------------- recherche

    public function test_search_returns_manual_fares_with_price_and_expiry(): void
    {
        $this->manualFare();
        $search = $this->search();

        $this->assertSame(1, $search->results_count);
        $offer = $search->offers()->first();
        $this->assertEquals(550000, (float) $offer->total_amount);
        $this->assertTrue($offer->expires_at->isFuture());
        $this->assertSame('2 x 23 kg', $offer->fare_rules['baggage']);

        $this->get("/voyage/vols/resultats/{$search->uuid}")->assertOk()
            ->assertInertia(fn ($page) => $page->component('Voyage/Vols/Results')->has('offers', 1));
    }

    public function test_round_trip_combines_outbound_and_return_and_prices_children_and_infants(): void
    {
        $this->manualFare();
        $this->manualFare(['origin' => 'CDG', 'destination' => 'BZV', 'departure_time' => '10:00', 'arrival_time' => '18:30', 'arrival_day_offset' => 0]);

        $search = $this->search(['trip_type' => 'roundtrip', 'return_date' => now()->addDays(35)->toDateString(), 'adults' => 2, 'children' => 1, 'infants' => 1]);
        $offer = $search->offers()->firstOrFail();

        $this->assertCount(2, $offer->slices);
        // Adulte 2 x 550 000 ; enfant 2 x (300 000 + 150 000) ; bebe 2 x 55 000
        $this->assertEquals(2 * 1100000 + 900000 + 110000, (float) $offer->total_amount);
    }

    public function test_search_without_result_and_invalid_search(): void
    {
        $search = $this->search(['destination' => 'DXB']);
        $this->assertSame(0, $search->results_count);
        $this->get("/voyage/vols/resultats/{$search->uuid}")->assertOk();

        $this->get('/voyage/vols/recherche?'.http_build_query($this->searchParams(['destination' => 'BZV'])))
            ->assertSessionHasErrors('destination');
    }

    public function test_multicity_search_combines_one_fare_per_leg_and_books(): void
    {
        $this->manualFare();
        $this->manualFare(['origin' => 'CDG', 'destination' => 'DXB', 'flight_number' => 'EK 74', 'base_fare' => 200000, 'taxes' => 50000, 'arrival_day_offset' => 0, 'arrival_time' => '22:30', 'departure_time' => '14:00']);
        $this->manualFare(['origin' => 'DXB', 'destination' => 'BZV', 'flight_number' => 'EK 781', 'base_fare' => 300000, 'taxes' => 100000, 'arrival_day_offset' => 0, 'arrival_time' => '15:00', 'departure_time' => '08:00']);

        $legs = [
            ['origin' => 'bzv', 'destination' => 'cdg', 'date' => now()->addDays(20)->toDateString()],
            ['origin' => 'CDG', 'destination' => 'DXB', 'date' => now()->addDays(27)->toDateString()],
            ['origin' => 'DXB', 'destination' => 'BZV', 'date' => now()->addDays(34)->toDateString()],
        ];
        $this->get('/voyage/vols/recherche?'.http_build_query(['trip_type' => 'multicity', 'legs' => $legs, 'adults' => 1, 'cabin' => 'economy']))->assertRedirect();
        $search = FlightSearch::latest('id')->firstOrFail();

        $this->assertSame('multicity', $search->trip_type);
        $this->assertCount(3, $search->legs);
        $this->assertSame(['BZV', 'BZV'], [$search->origin, $search->destination]);
        $this->assertSame(1, $search->results_count);

        $offer = $search->offers()->firstOrFail();
        $this->assertSame(['BZV', 'CDG', 'DXB'], array_column($offer->slices, 'origin'));
        $this->assertEquals(550000 + 250000 + 400000, (float) $offer->total_amount);

        $this->get("/voyage/vols/resultats/{$search->uuid}")->assertOk()
            ->assertInertia(fn ($page) => $page->component('Voyage/Vols/Results')->has('offers', 1)->has('airports', 3));

        // La revalidation du prix relit les 3 tarifs : le dossier s'ouvre normalement.
        $flight = $this->bookDossier($offer);
        $this->assertSame(FlightStatus::PRICE_CONFIRMED, $flight->status);
        $this->assertSame('multicity', $flight->trip_type);
        $this->assertCount(3, $flight->itinerary);
        $this->assertSame('Vol BZV -> CDG -> DXB -> BZV', $flight->title);
    }

    public function test_multicity_search_is_validated(): void
    {
        $date = now()->addDays(20)->toDateString();
        $url = fn (array $legs) => '/voyage/vols/recherche?'.http_build_query(['trip_type' => 'multicity', 'legs' => $legs, 'adults' => 1, 'cabin' => 'economy']);

        $this->get($url([['origin' => 'BZV', 'destination' => 'CDG', 'date' => $date]]))->assertSessionHasErrors('legs');
        $this->get($url([
            ['origin' => 'BZV', 'destination' => 'CDG', 'date' => $date],
            ['origin' => 'CDG', 'destination' => 'CDG', 'date' => $date],
        ]))->assertSessionHasErrors('legs.1.destination');
        $this->get($url([
            ['origin' => 'BZV', 'destination' => 'CDG', 'date' => $date],
            ['origin' => 'CDG', 'destination' => 'DXB', 'date' => now()->addDays(10)->toDateString()],
        ]))->assertSessionHasErrors('legs.1.date');
        $this->assertSame(0, FlightSearch::count());
    }

    public function test_multicity_sandbox_offers_have_one_slice_per_leg(): void
    {
        $this->useSandbox();
        $legs = [
            ['origin' => 'BZV', 'destination' => 'CDG', 'date' => now()->addDays(20)->toDateString()],
            ['origin' => 'CDG', 'destination' => 'DXB', 'date' => now()->addDays(25)->toDateString()],
        ];
        $res = $this->postJson('/api/flights/search', ['trip_type' => 'multicity', 'legs' => $legs, 'adults' => 1, 'cabin' => 'economy'])->assertCreated();
        $this->assertCount(2, $res->json('offers.0.slices'));
    }

    public function test_markup_and_service_fee_are_added_to_the_supplier_price(): void
    {
        config(['flights.markup_percent' => 5, 'flights.service_fee_per_passenger' => 10000]);
        $this->manualFare();
        $offer = $this->search()->offers()->first();

        $this->assertEquals(550000, (float) $offer->supplier_amount);
        $this->assertEquals(550000 + 27500 + 10000, (float) $offer->total_amount);
        $this->assertArrayNotHasKey('supplier_amount', $offer->toArray()); // jamais expose au client
    }

    // -------------------------------------------------------- dossier / prix

    public function test_passengers_submission_revalidates_price_and_opens_checkout(): void
    {
        $this->manualFare();
        $offer = $this->search()->offers()->first();
        $flight = $this->bookDossier($offer);

        $this->assertSame(FlightStatus::PRICE_CONFIRMED, $flight->status);
        $this->assertNotNull($flight->booking_id);
        $this->assertSame('awaiting_payment', $flight->booking->status);
        $this->assertEquals(550000, (float) $flight->booking->total_amount);
        $this->assertSame('OA1234567', $flight->passengers->first()->document_number);
        // Numero de passeport chiffre en base.
        $this->assertStringNotContainsString('OA1234567', (string) \DB::table('flight_passengers')->value('document_number'));
        $this->assertEquals([FlightStatus::DRAFT, FlightStatus::SEARCHED, FlightStatus::PRICE_CONFIRMED], $flight->histories->pluck('to_status')->all());
    }

    public function test_price_change_at_revalidation_blocks_the_booking(): void
    {
        $fare = $this->manualFare();
        $offer = $this->search()->offers()->first();
        $fare->update(['base_fare' => 450000]);

        $this->actingAs($this->client)
            ->post("/voyage/vols/offres/{$offer->uuid}/reserver", $this->passengerPayload($offer))
            ->assertSessionHas('error');

        $this->assertSame(0, FlightBooking::count());
        $this->assertEquals(600000, (float) $offer->fresh()->total_amount);

        // Le client confirme le nouveau prix affiche.
        $this->actingAs($this->client)
            ->post("/voyage/vols/offres/{$offer->uuid}/reserver", $this->passengerPayload($offer->fresh()))
            ->assertRedirect();
        $this->assertSame(FlightStatus::PRICE_CONFIRMED, FlightBooking::first()->status);
    }

    public function test_unavailable_offer_redirects_to_results(): void
    {
        $fare = $this->manualFare();
        $offer = $this->search()->offers()->first();
        $fare->update(['is_active' => false]);

        $this->actingAs($this->client)
            ->post("/voyage/vols/offres/{$offer->uuid}/reserver", $this->passengerPayload($offer))
            ->assertRedirect("/voyage/vols/resultats/{$offer->search->uuid}");
        $this->assertSame(0, FlightBooking::count());
    }

    public function test_passport_is_required_for_international_flights_and_age_matches_type(): void
    {
        $this->manualFare();
        $offer = $this->search(['children' => 1])->offers()->first();
        $payload = $this->passengerPayload($offer, ['ADT', 'CHD']);
        $payload['passengers'][1]['birth_date'] = '1990-01-01';

        $this->actingAs($this->client)->post("/voyage/vols/offres/{$offer->uuid}/reserver", $payload)
            ->assertSessionHasErrors('passengers.1.birth_date');

        $payload = $this->passengerPayload($offer, ['ADT', 'CHD']);
        $payload['passengers'][0]['document_number'] = null;
        $this->actingAs($this->client)->post("/voyage/vols/offres/{$offer->uuid}/reserver", $payload)
            ->assertSessionHas('error');
        $this->assertSame(0, FlightBooking::count());
    }

    public function test_expired_price_is_revalidated_before_payment(): void
    {
        $fare = $this->manualFare();
        $offer = $this->search()->offers()->first();
        $flight = $this->bookDossier($offer);

        $this->travel(45)->minutes();
        $fare->update(['taxes' => 175000]);

        try {
            app(\App\Services\Flights\FlightBookingService::class)->beforePayment($flight->booking->fresh());
            $this->fail('Le paiement aurait du etre bloque');
        } catch (\App\Services\Flights\FlightException $e) {
            $this->assertSame('price_changed', $e->reason);
        }

        $flight->refresh();
        $this->assertSame(FlightStatus::SEARCHED, $flight->status);
        $this->assertEquals(575000, (float) $flight->total_amount);
        $this->assertEquals(575000, (float) $flight->booking->total_amount);
        $this->assertSame(0, Payment::count());

        $this->actingAs($this->client)->post("/voyage/vols/dossiers/{$flight->reference}/confirmer-prix")
            ->assertRedirect("/checkout/{$flight->booking_id}");
        $this->assertSame(FlightStatus::PRICE_CONFIRMED, $flight->fresh()->status);
    }

    // ------------------------------------------------ paiement -> emission

    public function test_sandbox_full_flow_payment_booking_ticketing(): void
    {
        $this->useSandbox();
        $offer = $this->search(['destination' => 'DXB', 'adults' => 2])->offers()->first();
        $flight = $this->bookDossier($offer, ['ADT', 'ADT']);

        $this->pay($flight);
        $flight->refresh();

        $this->assertSame(FlightStatus::TICKETED, $flight->status);
        $this->assertNotNull($flight->pnr);
        $this->assertSame(2, $flight->tickets()->count());
        $this->assertSame('completed', $flight->booking->status);
        $this->assertEquals(
            [FlightStatus::DRAFT, FlightStatus::SEARCHED, FlightStatus::PRICE_CONFIRMED, FlightStatus::PAYMENT_PENDING, FlightStatus::PAYMENT_CONFIRMED,
                FlightStatus::BOOKING_PENDING, FlightStatus::BOOKED, FlightStatus::TICKETING_PENDING, FlightStatus::TICKETED],
            $flight->histories->pluck('to_status')->all(),
        );

        $this->actingAs($this->client)->get("/voyage/vols/dossiers/{$flight->reference}/document/billet")->assertOk();
    }

    public function test_duplicate_payment_notification_does_not_book_or_issue_twice(): void
    {
        $this->useSandbox();
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $payment = $this->pay($flight);

        // Callback PSP rejoue + relance manuelle du traitement.
        app(PaymentService::class)->markPayment($payment, true);
        app(FlightFulfillmentService::class)->process($flight->fresh());
        app(FlightFulfillmentService::class)->reconcile($flight->fresh());

        $this->assertSame(1, FlightSupplierLog::where('flight_booking_id', $flight->id)->where('operation', 'book')->count());
        $this->assertSame(1, FlightSupplierLog::where('flight_booking_id', $flight->id)->where('operation', 'issue')->count());
        $this->assertSame(1, $flight->tickets()->count());
    }

    public function test_second_payment_is_refused_while_one_is_pending(): void
    {
        $this->manualFare();
        $flight = $this->bookDossier($this->search()->offers()->first());
        app(\App\Services\Flights\FlightBookingService::class)->beforePayment($flight->booking);
        Payment::create(['booking_id' => $flight->booking_id, 'user_id' => $this->client->id, 'provider' => 'peex', 'method' => 'mobile_money',
            'phone' => '242060000000', 'country_code' => 'CG', 'amount' => 1, 'currency' => 'XAF', 'status' => 'pending']);

        try {
            app(\App\Services\Flights\FlightBookingService::class)->beforePayment($flight->booking->fresh());
            $this->fail('Un second paiement aurait du etre refuse');
        } catch (\App\Services\Flights\FlightException $e) {
            $this->assertSame('payment_in_progress', $e->reason);
        }
    }

    public function test_failed_payment_returns_dossier_to_price_confirmed(): void
    {
        $this->manualFare();
        $flight = $this->bookDossier($this->search()->offers()->first());
        $this->pay($flight, false);

        $this->assertSame(FlightStatus::PRICE_CONFIRMED, $flight->fresh()->status);
        $this->assertNull($flight->fresh()->paid_at);
    }

    public function test_manual_supplier_flow_is_completed_by_the_ticketing_team(): void
    {
        $this->manualFare();
        $flight = $this->bookDossier($this->search()->offers()->first());
        $this->pay($flight);
        $flight->refresh();

        $this->assertSame(FlightStatus::BOOKING_PENDING, $flight->status);
        $this->assertTrue($flight->needs_attention);
        $this->assertSame('paid', $flight->payment_state);
        $this->assertSame('none', $flight->ticket_state);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post("/admin/vols/{$flight->reference}/pnr", ['pnr' => 'ABC123'])->assertSessionHas('success');
        $flight->refresh();
        $this->assertSame(FlightStatus::TICKETING_PENDING, $flight->status);
        $this->assertSame('confirmed', $flight->booking_state);

        // Le billet n'est pas consultable avant TICKETED.
        $this->actingAs($this->client)->get("/voyage/vols/dossiers/{$flight->reference}/document/billet")->assertNotFound();

        $passenger = $flight->passengers()->first();
        $this->actingAs($admin)->post("/admin/vols/{$flight->reference}/billets", ['tickets' => [$passenger->id => '0711234567890']])
            ->assertSessionHas('success');
        $this->assertSame(FlightStatus::TICKETED, $flight->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'flight.tickets', 'user_id' => $admin->id]);
    }

    public function test_booking_failure_after_payment_flags_dossier_for_compensation(): void
    {
        $this->useSandbox('book_fail');
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $this->pay($flight);
        $flight->refresh();

        $this->assertSame(FlightStatus::FAILED, $flight->status);
        $this->assertTrue($flight->needs_attention);
        $this->assertNotNull($flight->paid_at);
        $this->assertSame('failed', $flight->booking_state);

        app(FlightFulfillmentService::class)->startRefund($flight, null, 'Classe complete');
        $this->assertSame(FlightStatus::REFUND_PENDING, $flight->fresh()->status);
        $this->assertSame('pending', $flight->fresh()->refund_status);
    }

    public function test_issue_timeout_is_never_retried_blindly(): void
    {
        $this->useSandbox('issue_timeout');
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $this->pay($flight);
        $flight->refresh();
        $this->assertSame(FlightStatus::TICKETING_PENDING, $flight->status);
        $this->assertTrue($flight->needs_attention);

        // Le fournisseur indique que l'emission a en fait abouti.
        config(['flights.sandbox_behaviour' => null, 'flights.sandbox_timeout_succeeded' => true]);
        app(FlightFulfillmentService::class)->reconcile($flight);

        $this->assertSame(FlightStatus::TICKETED, $flight->fresh()->status);
        $this->assertSame(1, FlightSupplierLog::where('flight_booking_id', $flight->id)->where('operation', 'issue')->count());
    }

    public function test_book_timeout_is_retried_only_after_supplier_confirms_nothing_was_created(): void
    {
        $this->useSandbox('book_timeout');
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $this->pay($flight);
        $this->assertSame(FlightStatus::BOOKING_PENDING, $flight->fresh()->status);

        config(['flights.sandbox_behaviour' => null, 'flights.sandbox_timeout_succeeded' => false]);
        app(FlightFulfillmentService::class)->reconcile($flight);

        $this->assertSame(FlightStatus::TICKETED, $flight->fresh()->status);
        $this->assertSame(2, FlightSupplierLog::where('flight_booking_id', $flight->id)->where('operation', 'book')->count());
    }

    public function test_reconcile_command_runs_on_stale_dossiers(): void
    {
        $this->useSandbox('book_timeout');
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $this->pay($flight);

        config(['flights.sandbox_behaviour' => null]);
        $this->travel(15)->minutes();
        $this->artisan('flights:reconcile')->assertSuccessful();
        $this->assertSame(FlightStatus::TICKETED, $flight->fresh()->status);
    }

    // ------------------------------------------------- securite / machine

    public function test_state_machine_refuses_forbidden_transitions(): void
    {
        $this->manualFare();
        $flight = $this->bookDossier($this->search()->offers()->first());

        $this->expectException(InvalidFlightTransition::class);
        app(FlightStateMachine::class)->transition($flight, FlightStatus::TICKETED);
    }

    public function test_a_client_never_sees_another_clients_dossier(): void
    {
        $this->manualFare();
        $flight = $this->bookDossier($this->search()->offers()->first());
        $other = User::factory()->create(['role' => 'client']);

        $this->actingAs($other)->get("/voyage/vols/dossiers/{$flight->reference}")->assertForbidden();
        $this->actingAs($other)->getJson("/api/flights/bookings/{$flight->reference}")->assertForbidden();
        $this->actingAs($this->client)->get("/voyage/vols/dossiers/{$flight->reference}")->assertOk();
        $this->actingAs($other)->get('/admin/vols')->assertForbidden();
    }

    public function test_after_sale_request_online(): void
    {
        $this->useSandbox();
        $flight = $this->bookDossier($this->search(['destination' => 'DXB'])->offers()->first());
        $this->pay($flight);

        $this->actingAs($this->client)->post("/voyage/vols/dossiers/{$flight->reference}/apres-vente", [
            'type' => 'cancellation', 'reason' => 'Mon voyage est reporte pour raison professionnelle.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('flight_after_sales', ['flight_booking_id' => $flight->id, 'status' => 'submitted']);
        $this->assertTrue($flight->fresh()->needs_attention);
    }

    public function test_api_search_and_booking_for_mobile_app(): void
    {
        $this->manualFare();
        $res = $this->postJson('/api/flights/search', $this->searchParams())->assertCreated();
        $uuid = $res->json('offers.0.uuid');
        $offer = FlightOffer::where('uuid', $uuid)->firstOrFail();

        $this->actingAs($this->client, 'sanctum')->postJson("/api/flights/offers/{$uuid}/book", $this->passengerPayload($offer))
            ->assertCreated()->assertJsonPath('dossier.status', FlightStatus::PRICE_CONFIRMED);
    }

    public function test_generic_booking_is_refused_for_flight_offers(): void
    {
        $offer = \App\Models\Voyage\TravelOffer::create([
            'title' => 'Promo Paris', 'type' => 'vol', 'destination_city' => 'Paris', 'destination_country' => 'FR',
            'origin_city' => 'Brazzaville', 'price' => 500000, 'currency' => 'XAF', 'seats_available' => 5,
        ]);
        $this->actingAs($this->client)->post("/voyage/{$offer->slug}/reserver", ['quantity' => 1])->assertRedirect('/voyage?type=vol');
        $this->assertSame(0, Booking::count());
    }

    public function test_back_office_and_client_pages_render(): void
    {
        $fare = $this->manualFare();
        $offer = $this->search()->offers()->first();
        $this->actingAs($this->client)->get("/voyage/vols/offres/{$offer->uuid}")->assertOk()
            ->assertInertia(fn ($page) => $page->component('Voyage/Vols/Book')->where('international', true));
        $flight = $this->bookDossier($offer);
        $this->actingAs($this->client)->get('/voyage/vols/mes-billets')->assertOk();
        $this->actingAs($this->client)->get("/checkout/{$flight->booking_id}")->assertOk();
        $this->get('/voyage?type=vol')->assertOk()->assertInertia(fn ($page) => $page->has('popularAirports'));
        $this->getJson('/voyage/vols/aeroports?q=braz')->assertOk()->assertJsonPath('0.iata_code', 'BZV');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/vols')->assertOk();
        $this->actingAs($admin)->get("/admin/vols/{$flight->reference}")->assertOk()
            ->assertInertia(fn ($page) => $page->where('passengers.0.document_number', 'OA1234567'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'flight.view', 'user_id' => $admin->id]);
        $this->actingAs($admin)->get('/admin/vols/tarifs')->assertOk();
        $this->actingAs($admin)->get('/admin/vols/tarifs/creer')->assertOk();
        $this->actingAs($admin)->get("/admin/vols/tarifs/{$fare->id}/editer")->assertOk();
        $this->actingAs($admin)->get('/admin/voyage/creer')->assertOk();
    }

    public function test_admin_can_create_a_negotiated_fare(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/vols/tarifs', [
            'flight_supplier_id' => FlightSupplier::where('code', 'manuel')->value('id'),
            'airline_code' => 'wb', 'airline_name' => 'RwandAir', 'flight_number' => 'WB 201',
            'origin' => 'bzv', 'destination' => 'kgl', 'departure_time' => '14:05', 'arrival_time' => '18:20',
            'arrival_day_offset' => 0, 'stops' => 0, 'days_of_week' => [1, 3, 5], 'cabin' => 'economy',
            'base_fare' => 210000, 'taxes' => 95000, 'child_percent' => 75, 'infant_percent' => 10,
            'currency' => 'XAF', 'ticketing_delay_hours' => 24, 'refundable' => false, 'changeable' => true, 'is_active' => true,
        ])->assertRedirect('/admin/vols/tarifs');
        $this->assertDatabaseHas('flight_fares', ['origin' => 'BZV', 'destination' => 'KGL', 'airline_code' => 'WB']);
    }
}
