<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voyage\TravelOffer;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rubrique Circuits facon BlaBlaCar : trajets bus / train / taxi, reservation et paiement. */
class RidesTest extends TestCase
{
    use RefreshDatabase;

    protected function ride(array $attrs = []): TravelOffer
    {
        return TravelOffer::create($attrs + [
            'title' => 'Brazzaville - Pointe-Noire', 'type' => 'circuit', 'transport_mode' => 'bus',
            'origin_city' => 'Brazzaville', 'destination_city' => 'Pointe-Noire', 'destination_country' => 'CG',
            'departure_at' => now()->addDays(3)->setTime(7, 0), 'arrival_at' => now()->addDays(3)->setTime(15, 30),
            'price' => 15000, 'currency' => 'XAF', 'seats_available' => 4, 'airline' => 'Ocean du Nord', 'is_active' => true,
        ]);
    }

    public function test_sejours_are_removed_and_rides_are_searched_like_blablacar(): void
    {
        $bus = $this->ride();
        $this->ride(['title' => 'Train', 'transport_mode' => 'train', 'departure_at' => now()->addDays(5)]);
        $this->ride(['title' => 'Taxi Dolisie', 'transport_mode' => 'taxi', 'destination_city' => 'Dolisie', 'seats_available' => 1]);
        $this->ride(['title' => 'Parti hier', 'departure_at' => now()->subDay()]);
        TravelOffer::create(['title' => 'Sejour a Paris', 'type' => 'sejour', 'destination_city' => 'Paris', 'destination_country' => 'FR', 'price' => 1, 'currency' => 'XAF', 'seats_available' => 5]);

        $this->get('/voyage')->assertOk()->assertInertia(fn ($p) => $p->where('offers.data', fn ($d) => collect($d)->every(fn ($o) => $o['type'] !== 'sejour')));

        $date = $bus->departure_at->toDateString();
        $this->get("/voyage?type=circuit&from=Brazza&destination=Pointe&date={$date}&passengers=2")->assertOk()
            ->assertInertia(fn ($p) => $p->has('offers.data', 1)->where('offers.data.0.id', $bus->id)->where('filters.passengers', '2'));
        $this->get('/voyage?type=circuit&mode=taxi')->assertInertia(fn ($p) => $p->has('offers.data', 1)->where('offers.data.0.transport_mode_label', 'Taxi interurbain'));
        $this->get('/voyage?type=circuit&passengers=2')->assertInertia(fn ($p) => $p->has('offers.data', 2)); // taxi (1 place) et trajet passe exclus
        $this->getJson('/api/voyage/offers?type=circuit&from=Brazzaville&passengers=3')->assertOk()->assertJsonCount(2, 'data'); // bus + train, sans le trajet passe
    }

    public function test_sncf_style_search_hour_return_and_no_flights_in_rides(): void
    {
        $d = now()->addDays(3);
        $early = $this->ride(['departure_at' => $d->copy()->setTime(7, 0)]);
        $late = $this->ride(['title' => 'Bus du soir', 'departure_at' => $d->copy()->setTime(17, 0)]);
        $this->ride(['title' => 'Sejour a Paris', 'transport_mode' => 'avion', 'destination_city' => 'Paris']);
        $back = $this->ride(['title' => 'Retour', 'origin_city' => 'Pointe-Noire', 'destination_city' => 'Brazzaville', 'departure_at' => now()->addDays(6)->setTime(9, 0)]);
        $this->ride(['title' => 'Retour autre jour', 'origin_city' => 'Pointe-Noire', 'destination_city' => 'Brazzaville', 'departure_at' => now()->addDays(8)->setTime(9, 0)]);

        $this->get('/voyage?type=circuit')->assertInertia(fn ($p) => $p->where('offers.data', fn ($o) => collect($o)->every(fn ($x) => $x['transport_mode'] !== 'avion')));

        $this->get('/voyage?type=circuit&from=Brazzaville&destination=Pointe-Noire&date='.$d->toDateString().'&time=16&return_date='.now()->addDays(6)->toDateString())
            ->assertOk()->assertInertia(fn ($p) => $p->has('offers.data', 1)->where('offers.data.0.id', $late->id)
                ->has('returnOffers', 1)->where('returnOffers.0.id', $back->id)->where('filters.time', '16'));

        $this->get('/voyage?type=circuit&from=Brazzaville&date='.$d->toDateString())->assertInertia(fn ($p) => $p->has('offers.data', 2)->has('returnOffers', 0));
        $this->assertNotNull($early);

        // Meme recherche depuis l'application mobile (API).
        $this->getJson('/api/voyage/offers?type=circuit&from=Brazzaville&destination=Pointe-Noire&date='.$d->toDateString().'&time=16&return_date='.now()->addDays(6)->toDateString())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $late->id)
            ->assertJsonCount(1, 'return_offers')->assertJsonPath('return_offers.0.id', $back->id);
        $this->getJson('/api/voyage/offers?type=circuit')->assertJsonMissing(['transport_mode' => 'avion']);

        // Reservation depuis l'application : places limitees, message au transporteur.
        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::factory()->create(['role' => 'client']));
        $this->postJson("/api/voyage/offers/{$late->slug}/book", ['quantity' => 9])->assertStatus(422);
        $this->postJson("/api/voyage/offers/{$late->slug}/book", ['quantity' => 2, 'notes' => 'Deux valises'])->assertCreated()
            ->assertJsonPath('notes', 'Deux valises')->assertJsonPath('total_amount', fn ($v) => (float) $v === 30000.0);
    }

    public function test_booking_is_limited_to_remaining_seats_and_payment_takes_the_seats(): void
    {
        $ride = $this->ride();
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)->post("/voyage/{$ride->slug}/reserver", ['quantity' => 5])->assertSessionHasErrors('quantity');
        $this->assertSame(0, Booking::count());

        $this->actingAs($user)->post("/voyage/{$ride->slug}/reserver", ['quantity' => 3])->assertRedirect();
        $booking = Booking::firstOrFail();
        $this->assertEquals(45000, (float) $booking->total_amount);

        $payment = Payment::create([
            'booking_id' => $booking->id, 'user_id' => $user->id, 'provider' => 'maishapay', 'method' => 'mobile_money',
            'phone' => '242060000000', 'country_code' => 'CG', 'amount' => $booking->total_amount, 'currency' => 'XAF', 'status' => 'pending',
        ]);
        app(PaymentService::class)->markPayment($payment, true);
        app(PaymentService::class)->markPayment($payment->fresh(), true); // notification en double : pas de double decompte

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, (int) $ride->fresh()->seats_available);
    }

    public function test_departed_ride_cannot_be_booked(): void
    {
        $ride = $this->ride(['departure_at' => now()->subHour()]);
        $this->actingAs(User::factory()->create(['role' => 'client']))->post("/voyage/{$ride->slug}/reserver", ['quantity' => 1])
            ->assertSessionHas('error');
        $this->assertSame(0, Booking::count());
    }

    public function test_admin_can_create_a_regular_trip_on_chosen_weekdays(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-09 10:00')); // lundi
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/voyage', [
            'title' => 'Ocean du Nord Brazzaville - Pointe-Noire', 'type' => 'circuit', 'transport_mode' => 'bus',
            'origin_city' => 'Brazzaville', 'destination_city' => 'Pointe-Noire', 'destination_country' => 'CG',
            'departure_at' => '2026-11-10T06:30', 'arrival_at' => '2026-11-10T15:00', // mardi
            'price' => 15000, 'currency' => 'XAF', 'seats_available' => 50, 'is_active' => true,
            'repeat_days' => [2, 5], 'repeat_until' => '2026-11-24', // mardis et vendredis
        ])->assertRedirect('/admin/voyage');

        $trips = TravelOffer::orderBy('departure_at')->get();
        $this->assertSame(
            ['2026-11-10 06:30', '2026-11-13 06:30', '2026-11-17 06:30', '2026-11-20 06:30', '2026-11-24 06:30'],
            $trips->map(fn ($t) => $t->departure_at->format('Y-m-d H:i'))->all(),
        );
        $this->assertSame('2026-11-24 15:00', $trips->last()->arrival_at->format('Y-m-d H:i'));
        $this->assertCount(5, $trips->pluck('slug')->unique());
    }

    public function test_blablacar_style_day_prices_and_popular_routes(): void
    {
        \App\Models\Voyage\TravelOffer::query()->delete();
        $mk = fn ($from, $to, $price, $days) => \App\Models\Voyage\TravelOffer::create(['title' => "$from - $to", 'type' => 'circuit', 'transport_mode' => 'bus', 'origin_city' => $from, 'destination_city' => $to, 'destination_country' => 'CG', 'price' => $price, 'currency' => 'XAF', 'seats_available' => 10, 'is_active' => true, 'departure_at' => now()->startOfDay()->addDays($days)->setTime(8, 0), 'arrival_at' => now()->startOfDay()->addDays($days)->setTime(16, 0), 'arrival_point' => 'Gare Tie-Tie', 'amenities' => ['wifi']]);
        $mk('Brazzaville', 'Pointe-Noire', 15000, 1);
        $mk('Brazzaville', 'Pointe-Noire', 12000, 1);
        $mk('Brazzaville', 'Pointe-Noire', 14000, 2);
        $mk('Dolisie', 'Pointe-Noire', 6000, 2);

        $d1 = now()->addDay()->toDateString();
        $this->get('/voyage?type=circuit&from=Brazzaville&destination=Pointe-Noire')->assertInertia(fn ($p) => $p
            ->where("dayPrices.$d1", 12000)
            ->where('popularRoutes.0.to', 'Pointe-Noire')->where('popularRoutes.0.from', 'Brazzaville')->where('popularRoutes.0.price', 12000)
            ->where('popularRoutes.0.others.0.from', 'Dolisie')
            ->where('offers.data.0.arrival_point', 'Gare Tie-Tie'));
    }
}
