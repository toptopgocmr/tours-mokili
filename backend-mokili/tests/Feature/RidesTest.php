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
}
