<?php

namespace Tests\Feature;

use App\Models\Voyage\TravelOffer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
