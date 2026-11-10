<?php

namespace Tests\Feature;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightSearch;
use App\Models\Flights\FlightSupplier;
use App\Models\Payment;
use App\Models\User;
use App\Services\Flights\FlightBookingService;
use App\Services\Flights\FlightStatus;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DuffelSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flights.duffel.token' => 'duffel_test_xxx']);
        FlightSupplier::where('code', 'manuel')->update(['is_active' => false]);
        $this->client = User::factory()->create(['role' => 'client', 'phone' => '+242060000000']);
        $this->travelTo('2026-11-10 10:00');
    }

    protected function seg(string $from, string $to, string $dep, string $arr, string $carrier = 'ET', string $num = '861', string $dur = 'PT5H30M'): array
    {
        return [
            'origin' => ['iata_code' => $from], 'destination' => ['iata_code' => $to],
            'departing_at' => $dep, 'arriving_at' => $arr, 'duration' => $dur,
            'marketing_carrier' => ['iata_code' => $carrier, 'name' => $carrier === 'ET' ? 'Ethiopian Airlines' : 'ASKY Airlines'],
            'operating_carrier' => ['iata_code' => $carrier, 'name' => 'x'],
            'marketing_carrier_flight_number' => $num, 'aircraft' => ['name' => 'Boeing 787-8'],
            'passengers' => [['passenger_id' => 'pas_1', 'baggages' => [['type' => 'checked', 'quantity' => 2], ['type' => 'carry_on', 'quantity' => 1]]]],
        ];
    }

    protected function offer(string $id = 'off_1', string $amount = '1000.00', string $currency = 'EUR'): array
    {
        return [
            'id' => $id, 'total_amount' => $amount, 'total_currency' => $currency, 'expires_at' => '2026-11-10T11:00:00Z',
            'owner' => ['iata_code' => 'ET', 'name' => 'Ethiopian Airlines'],
            'passengers' => [['id' => 'pas_1', 'type' => 'adult']],
            'conditions' => ['refund_before_departure' => ['allowed' => true, 'penalty_amount' => '100.00', 'penalty_currency' => 'EUR'], 'change_before_departure' => ['allowed' => true]],
            'slices' => [
                ['duration' => 'PT14H45M', 'segments' => [
                    $this->seg('BZV', 'ADD', '2026-12-14T12:10:00', '2026-12-14T20:40:00'),
                    $this->seg('ADD', 'DXB', '2026-12-15T01:30:00', '2026-12-15T06:30:00', 'ET', '600', 'PT4H'),
                ]],
            ],
        ];
    }

    protected function search(): FlightSearch
    {
        $this->get('/voyage/vols/recherche?'.http_build_query([
            'trip_type' => 'oneway', 'origin' => 'BZV', 'destination' => 'DXB', 'depart_date' => '2026-12-14', 'adults' => 1, 'cabin' => 'economy',
        ]))->assertRedirect();

        return FlightSearch::latest('id')->firstOrFail();
    }

    public function test_duffel_search_returns_real_offers_converted_to_xaf(): void
    {
        Http::fake(['api.duffel.com/air/offer_requests*' => Http::response(['data' => ['id' => 'orq_1', 'offers' => [
            $this->offer(), $this->offer('off_usd', '900.00', 'USD'), // USD sans taux FX : ignore
        ]]])]);

        $search = $this->search();
        $this->assertSame(1, $search->results_count);
        $o = $search->offers()->firstOrFail();

        $this->assertEquals(655957, (float) $o->total_amount); // 1000 EUR au taux fixe
        $this->assertSame('XAF', $o->currency);
        $this->assertSame(['BZV', 'DXB', 1, ['ADD']], [$o->slices[0]['origin'], $o->slices[0]['destination'], $o->slices[0]['stops'], $o->slices[0]['stop_airports']]);
        $this->assertSame(885, $o->slices[0]['duration_minutes']);
        $this->assertSame('ET 861', $o->slices[0]['segments'][0]['flight_number']);
        $this->assertSame('2 bagages en soute', $o->fare_rules['baggage']);
        $this->assertEquals(65596, $o->fare_rules['cancel_fee']);
        $this->assertSame('off_1', $o->raw['duffel']['offer_id']);

        Http::assertSent(fn (Request $r) => $r->hasHeader('Duffel-Version', 'v2') && $r->hasHeader('Authorization', 'Bearer duffel_test_xxx')
            && $r['data']['slices'][0]['destination'] === 'DXB' && $r['data']['cabin_class'] === 'economy');
    }

    public function test_duffel_is_ignored_without_token(): void
    {
        config(['flights.duffel.token' => null]);
        Http::fake();
        $this->assertSame(0, $this->search()->results_count);
        Http::assertNothingSent();
    }

    public function test_auto_ticketing_creates_the_order_after_mobile_money_payment(): void
    {
        config(['flights.duffel.auto_ticketing' => true]);
        Http::fake([
            'api.duffel.com/air/offer_requests*' => Http::response(['data' => ['offers' => [$this->offer()]]]),
            'api.duffel.com/air/offers/off_1*' => Http::response(['data' => $this->offer()]),
            'api.duffel.com/air/orders' => Http::response(['data' => [
                'id' => 'ord_1', 'booking_reference' => 'RZPNX8', 'passengers' => [['id' => 'pas_1']],
                'documents' => [['type' => 'electronic_ticket', 'unique_identifier' => '0712345678901', 'passenger_ids' => ['pas_1']]],
            ]], 201),
            'api.duffel.com/air/orders/ord_1' => Http::response(['data' => [
                'id' => 'ord_1', 'booking_reference' => 'RZPNX8', 'passengers' => [['id' => 'pas_1']],
                'documents' => [['type' => 'electronic_ticket', 'unique_identifier' => '0712345678901', 'passenger_ids' => ['pas_1']]],
            ]]),
        ]);

        $offer = $this->search()->offers()->firstOrFail();
        $this->actingAs($this->client)->post("/voyage/vols/offres/{$offer->uuid}/reserver", [
            'contact_name' => 'Jean Mabiala', 'contact_email' => 'jean@example.com', 'contact_phone' => '+242 06 000 0000',
            'expected_total' => $offer->total_amount, 'accept_conditions' => true,
            'passengers' => [[
                'type' => 'ADT', 'title' => 'M.', 'gender' => 'M', 'first_name' => 'Jean', 'last_name' => 'Mabiala', 'birth_date' => '1990-04-12',
                'nationality' => 'CG', 'document_type' => 'passport', 'document_number' => 'OA1234567', 'document_expiry' => '2029-01-01', 'document_country' => 'CG',
            ]],
        ])->assertRedirect();
        $flight = FlightBooking::latest('id')->firstOrFail();

        app(FlightBookingService::class)->beforePayment($flight->booking->fresh());
        $payment = Payment::create([
            'booking_id' => $flight->booking_id, 'user_id' => $this->client->id, 'provider' => 'maishapay', 'method' => 'mobile_money',
            'phone' => '242060000000', 'country_code' => 'CG', 'amount' => $flight->booking->total_amount, 'currency' => 'XAF', 'status' => 'pending',
        ]);
        app(PaymentService::class)->markPayment($payment, true);
        $flight->refresh();

        $this->assertSame(FlightStatus::TICKETED, $flight->status);
        $this->assertSame('RZPNX8', $flight->pnr);
        $this->assertSame('0712345678901', $flight->tickets()->value('ticket_number'));
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.duffel.com/air/orders'
            && $r['data']['payments'][0] === ['type' => 'balance', 'currency' => 'EUR', 'amount' => '1000.00']
            && $r['data']['passengers'][0]['id'] === 'pas_1' && $r['data']['passengers'][0]['phone_number'] === '+242060000000'
            && $r['data']['passengers'][0]['identity_documents'][0]['unique_identifier'] === 'OA1234567');
    }
}
