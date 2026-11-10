<?php

namespace Tests\Feature;

use App\Models\Voyage\TravelOffer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Programme des departs / arrivees : trains, bus, canots rapides, taxis. */
class RideScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function ride(array $a): TravelOffer
    {
        return TravelOffer::create($a + [
            'title' => 'Trajet', 'type' => 'circuit', 'transport_mode' => 'bus', 'origin_city' => 'Brazzaville',
            'destination_city' => 'Pointe-Noire', 'destination_country' => 'CG', 'price' => 15000, 'currency' => 'XAF',
            'seats_available' => 10, 'airline' => 'Ocean du Nord', 'is_active' => true,
        ]);
    }

    public function test_board_lists_todays_departures_with_live_status(): void
    {
        // 10:00 a Brazzaville (= 09:00 UTC).
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00', 'UTC'));
        $d = '2026-11-10';
        $this->ride(['title' => 'Arrive', 'departure_at' => "$d 05:00", 'arrival_at' => "$d 09:00"]);
        $this->ride(['title' => 'En route', 'transport_mode' => 'train', 'airline' => 'CFCO', 'departure_at' => "$d 08:00", 'arrival_at' => "$d 16:00"]);
        $this->ride(['title' => 'Embarquement', 'transport_mode' => 'canot', 'destination_city' => 'Kinshasa', 'departure_at' => "$d 10:20", 'arrival_at' => "$d 10:50"]);
        $this->ride(['title' => 'Complet', 'transport_mode' => 'taxi', 'departure_at' => "$d 15:00", 'seats_available' => 0]);
        $this->ride(['title' => 'Prevu', 'departure_at' => "$d 17:00", 'arrival_at' => '2026-11-11 01:00']);
        $this->ride(['title' => 'Demain', 'departure_at' => '2026-11-11 07:00']);
        $this->ride(['title' => 'Avion', 'transport_mode' => 'avion', 'departure_at' => "$d 12:00"]);
        $this->ride(['title' => 'Autre ville', 'origin_city' => 'Dolisie', 'departure_at' => "$d 12:00"]);

        $this->get('/voyage/trajets/horaires')->assertOk()->assertInertia(fn ($p) => $p
            ->component('Voyage/Trajets/Horaires')->where('date', $d)->where('city', 'Brazzaville')
            ->has('rides', 5)
            ->where('rides.0.status', 'arrived')->where('rides.1.status', 'en_route')
            ->where('rides.2.status', 'boarding')->where('rides.3.status', 'full')->where('rides.3.bookable', false)
            ->where('rides.4.status', 'scheduled')->where('rides.4.bookable', true)
            ->where('cities', fn ($c) => collect($c)->contains('Dolisie')));

        $this->getJson('/api/voyage/schedules?mode=canot')->assertOk()->assertJsonCount(1, 'rides')->assertJsonPath('rides.0.destination', 'Kinshasa');
        $this->getJson('/api/voyage/schedules?city=Pointe-Noire&direction=arrivals')->assertOk()->assertJsonCount(4, 'rides');
        $this->getJson('/api/voyage/schedules?date=2026-11-11')->assertJsonCount(1, 'rides')->assertJsonPath('rides.0.departure_at', '2026-11-11T07:00');
    }

    public function test_empty_day_shows_next_departures(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00', 'UTC'));
        $this->ride(['title' => 'Vendredi', 'departure_at' => '2026-11-13 07:00']);
        $this->ride(['title' => 'Dans un mois', 'departure_at' => '2026-12-20 07:00']);

        $this->get('/voyage/trajets/horaires')->assertInertia(fn ($p) => $p->where('zone', 'congo')->has('rides', 0)
            ->has('upcoming', 1)->where('upcoming.0.departure_at', '2026-11-13T07:00'));
    }

    public function test_world_board_uses_transitland(): void
    {
        config(['services.transitland.key' => 'tl_xxx']);
        Http::fake([
            'transit.land/api/v2/rest/stops?*' => Http::response(['stops' => [
                ['onestop_id' => 's-u09-quai1', 'stop_name' => 'Paris Gare de Lyon', 'location_type' => 0, 'place' => ['adm0_name' => 'France', 'adm0_iso' => 'FR']],
                ['onestop_id' => 's-u09-gdl', 'stop_name' => 'Paris Gare de Lyon', 'location_type' => 1, 'place' => ['adm0_name' => 'France', 'adm0_iso' => 'FR', 'adm1_name' => 'Ile-de-France']],
            ]]),
            'transit.land/api/v2/rest/stops/s-u09-gdl/departures*' => Http::response(['stops' => [[
                'onestop_id' => 's-u09-gdl', 'stop_name' => 'Paris Gare de Lyon', 'stop_timezone' => 'Europe/Paris', 'platform_code' => 'K',
                'place' => ['adm0_name' => 'France'],
                'departures' => [
                    ['departure_time' => '18:12:00', 'date' => '2026-11-10', 'stop_headsign' => 'Marseille St-Charles',
                        'departure' => ['scheduled' => '18:12:00', 'estimated' => '18:20:00', 'estimated_delay' => 480],
                        'trip' => ['trip_headsign' => 'Marseille', 'trip_short_name' => '6107', 'route' => ['route_short_name' => 'TGV INOUI', 'route_type' => 2, 'route_color' => 'A1006B', 'agency' => ['agency_name' => 'SNCF']]]],
                    ['departure_time' => '18:05:00', 'date' => '2026-11-10',
                        'departure' => ['scheduled' => '18:05:00'],
                        'trip' => ['trip_headsign' => 'Gare du Nord', 'route' => ['route_short_name' => '91', 'route_type' => 3, 'agency' => ['agency_name' => 'RATP']]]],
                ],
            ]]]),
        ]);

        $this->getJson('/voyage/trajets/gares?q=gare de lyon')->assertOk()
            ->assertJsonCount(1, 'stations')->assertJsonPath('stations.0.id', 's-u09-gdl')->assertJsonPath('stations.0.kind', 'Gare');

        $this->get('/voyage/trajets/horaires?zone=monde&gare=s-u09-gdl&nom=Paris')->assertOk()->assertInertia(fn ($p) => $p
            ->where('zone', 'monde')->where('world.configured', true)->where('world.station.timezone', 'Europe/Paris')
            ->has('world.departures', 2)
            ->where('world.departures.0.time', '18:05')->where('world.departures.0.family', 'bus')
            ->where('world.departures.1.line', 'TGV INOUI')->where('world.departures.1.delay_minutes', 8)
            ->where('world.departures.1.color', '#A1006B')->where('world.departures.1.headsign', 'Marseille St-Charles'));

        $this->getJson('/api/voyage/world/departures?gare=s-u09-gdl&mode=train')->assertOk()->assertJsonCount(1, 'departures');
        Http::assertSent(fn (Request $r) => $r->hasHeader('apikey', 'tl_xxx'));
    }

    public function test_world_board_without_key_does_not_call_transitland(): void
    {
        config(['services.transitland.key' => null]);
        Http::fake();
        $this->get('/voyage/trajets/horaires?zone=monde&gare=s-u09-gdl')->assertInertia(fn ($p) => $p->where('world.configured', false));
        $this->getJson('/api/voyage/world/stations?q=paris')->assertJsonPath('configured', false);
        Http::assertNothingSent();
    }
}
