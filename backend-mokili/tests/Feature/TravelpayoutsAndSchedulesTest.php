<?php

namespace Tests\Feature;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightSearch;
use App\Models\Flights\FlightSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TravelpayoutsAndSchedulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flights.travelpayouts.token' => 'tp_xxx', 'flights.travelpayouts.marker' => '12345']);
        FlightSupplier::where('code', 'manuel')->update(['is_active' => false]);
        $this->travelTo('2026-11-10 10:00');
    }

    protected function ticket(array $over = []): array
    {
        return $over + [
            'origin' => 'BZV', 'destination' => 'PAR', 'origin_airport' => 'BZV', 'destination_airport' => 'CDG',
            'price' => 650, 'airline' => 'AF', 'flight_number' => '889', 'departure_at' => '2026-12-14T23:40:00+01:00',
            'transfers' => 0, 'duration' => 455, 'duration_to' => 455, 'link' => '/search/BZV1412CDG1?t=AF1',
        ];
    }

    protected function search(array $params): FlightSearch
    {
        $this->get('/voyage/vols/recherche?'.http_build_query($params + ['adults' => 1, 'cabin' => 'economy']))->assertRedirect();

        return FlightSearch::latest('id')->firstOrFail();
    }

    public function test_oneway_prices_become_indicative_offers_in_xaf(): void
    {
        Http::fake(['api.travelpayouts.com/aviasales/v3/prices_for_dates*' => Http::response(['success' => true, 'data' => [
            $this->ticket(),
            $this->ticket(['price' => 520, 'airline' => 'ET', 'flight_number' => '832', 'transfers' => 1, 'duration_to' => 900, 'departure_at' => '2026-12-14T12:10:00+01:00']),
            $this->ticket(['departure_at' => '2026-12-20T23:40:00+01:00']), // autre jour : ignore
        ]])]);

        $search = $this->search(['trip_type' => 'oneway', 'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => '2026-12-14']);
        $this->assertSame(2, $search->results_count);

        $o = $search->offers()->orderBy('total_amount')->firstOrFail();
        $this->assertEquals(round(520 * 655.957), (float) $o->total_amount);
        $this->assertSame('ET 832', $o->slices[0]['segments'][0]['flight_number']);
        $this->assertSame(1, $o->slices[0]['stops']);
        $this->assertSame('2026-12-14T12:10', $o->slices[0]['departure_at']);
        $this->assertSame('2026-12-15T03:10', $o->slices[0]['arrival_at']);
        $this->assertTrue($o->fare_rules['indicative']);
        $this->assertStringContainsString('marker=12345', $o->raw['travelpayouts']['links'][0]);

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Access-Token', 'tp_xxx') && $r['one_way'] === 'true' && $r['departure_at'] === '2026-12-14');
    }

    public function test_roundtrip_gives_two_slices_and_booking_waits_for_the_team(): void
    {
        Http::fake(['api.travelpayouts.com/*' => Http::response(['success' => true, 'data' => [
            $this->ticket(['return_at' => '2026-12-28T10:00:00+01:00', 'return_transfers' => 0, 'duration_back' => 470, 'price' => 900]),
        ]])]);

        $search = $this->search(['trip_type' => 'roundtrip', 'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => '2026-12-14', 'return_date' => '2026-12-28']);
        $o = $search->offers()->firstOrFail();
        $this->assertCount(2, $o->slices);
        $this->assertSame(['CDG', 'BZV'], [$o->slices[1]['origin'], $o->slices[1]['destination']]);

        $client = User::factory()->create(['role' => 'client', 'phone' => '+242060000000']);
        $this->actingAs($client)->post("/voyage/vols/offres/{$o->uuid}/reserver", [
            'contact_name' => 'Jean Mabiala', 'contact_email' => 'jean@example.com', 'contact_phone' => '+242 06 000 0000',
            'expected_total' => $o->total_amount, 'accept_conditions' => true,
            'passengers' => [[
                'type' => 'ADT', 'title' => 'M.', 'gender' => 'M', 'first_name' => 'Jean', 'last_name' => 'Mabiala', 'birth_date' => '1990-04-12',
                'nationality' => 'CG', 'document_type' => 'passport', 'document_number' => 'OA1234567', 'document_expiry' => '2029-01-01', 'document_country' => 'CG',
            ]],
        ])->assertRedirect();
        $this->assertNotNull(FlightBooking::latest('id')->first());
    }

    public function test_travelpayouts_is_ignored_without_token(): void
    {
        config(['flights.travelpayouts.token' => null]);
        Http::fake();
        $this->assertSame(0, $this->search(['trip_type' => 'oneway', 'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => '2026-12-14'])->results_count);
        Http::assertNothingSent();
    }

    public function test_schedule_board_shows_airlabs_departures(): void
    {
        config(['flights.airlabs.key' => 'al_xxx']);
        Http::fake(['airlabs.co/api/v9/schedules*' => Http::response(['response' => [
            ['flight_iata' => 'AF889', 'airline_iata' => 'AF', 'dep_iata' => 'BZV', 'arr_iata' => 'CDG', 'dep_time' => '2026-11-10 23:40', 'arr_time' => '2026-11-11 06:15', 'status' => 'scheduled', 'dep_delayed' => 20, 'dep_estimated' => '2026-11-11 00:00'],
            ['flight_iata' => 'KL1234', 'cs_flight_iata' => 'AF889', 'airline_iata' => 'KL', 'dep_iata' => 'BZV', 'arr_iata' => 'CDG', 'dep_time' => '2026-11-10 23:40'],
            ['flight_iata' => 'ET832', 'airline_iata' => 'ET', 'dep_iata' => 'BZV', 'arr_iata' => 'ADD', 'dep_time' => '2026-11-10 12:10', 'arr_time' => '2026-11-10 19:00', 'status' => 'active'],
        ]])]);

        $this->get('/voyage/vols/horaires?airport=bzv')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Voyage/Vols/Horaires')->where('airport', 'BZV')
                ->has('flights', 2)->where('flights.0.flight', 'ET832')->where('flights.1.delay_minutes', 20));

        $this->getJson('/api/flights/schedules?airport=BZV&direction=arrivals')->assertOk()->assertJsonPath('direction', 'arrivals');
        Http::assertSent(fn (Request $r) => $r['api_key'] === 'al_xxx' && ($r['arr_iata'] ?? null) === 'BZV');
    }

    public function test_schedule_board_without_key_does_not_call_airlabs(): void
    {
        Http::fake();
        $this->get('/voyage/vols/horaires')->assertOk()->assertInertia(fn ($p) => $p->where('error', 'not_configured'));
        Http::assertNothingSent();
    }
}
