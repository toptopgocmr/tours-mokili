<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Voyage\TravelOffer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Espace partenaires : transporteurs, compagnies aeriennes, agences, garages. */
class PartnerTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function partner(array $a = []): User
    {
        return User::factory()->create($a + [
            'role' => 'partner', 'company_name' => 'Ocean du Nord', 'partner_activity' => 'transport_routier', 'partner_verified_at' => now(),
        ]);
    }

    public function test_company_registers_and_offers_wait_for_admin_validation(): void
    {
        $this->post('/devenir-partenaire', [
            'company_name' => 'Beach Express', 'partner_activity' => 'canot_rapide', 'name' => 'Jean Mabiala',
            'email' => 'beach@example.com', 'phone' => '+242060000111', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'accept' => true,
        ])->assertRedirect('/partner');
        $u = User::where('email', 'beach@example.com')->firstOrFail();
        $this->assertSame('partner', $u->role);
        $this->assertNull($u->partner_verified_at);

        $this->actingAs($u)->post('/partner/transport', [
            'origin_city' => 'Brazzaville', 'destination_city' => 'Kinshasa', 'departure_at' => now()->addDays(2)->format('Y-m-d 10:00'),
            'price' => 8000, 'seats_available' => 20, 'transport_mode' => 'canot', 'is_active' => true,
        ])->assertRedirect();
        $trip = TravelOffer::firstOrFail();
        $this->assertFalse($trip->is_active); // pas encore valide
        $this->assertSame('Beach Express', $trip->airline);
        $this->assertSame('canot', $trip->transport_mode);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post("/admin/utilisateurs/{$u->id}/valider-partenaire")->assertRedirect();
        $this->assertNotNull($u->fresh()->partner_verified_at);
        $this->assertTrue($trip->fresh()->is_active);
    }

    public function test_partner_creates_regular_trips_and_only_sees_his_own(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 08:00')); // lundi
        $p = $this->partner();
        $other = $this->partner(['company_name' => 'Autre', 'email' => 'x@y.cg']);
        TravelOffer::create(['title' => 'Autre', 'type' => 'circuit', 'transport_mode' => 'bus', 'origin_city' => 'A', 'destination_city' => 'B', 'destination_country' => 'CG', 'price' => 1, 'currency' => 'XAF', 'seats_available' => 1, 'owner_id' => $other->id, 'departure_at' => now()->addDay()]);

        $this->actingAs($p)->post('/partner/transport', [
            'origin_city' => 'Brazzaville', 'destination_city' => 'Pointe-Noire', 'departure_point' => 'Agence Moungali',
            'departure_at' => '2026-11-10 07:00', 'arrival_at' => '2026-11-10 15:00', 'price' => 15000, 'seats_available' => 50,
            'transport_mode' => 'bus', 'is_active' => true,
            'repeat_days' => [2, 4], 'repeat_until' => '2026-11-20',
        ])->assertSessionHas('success', 'Trajet cree pour 4 departs.');
        $this->assertSame(4, $p->travelOffers()->count());
        $this->assertTrue($p->travelOffers()->first()->is_active);

        $this->actingAs($p)->get('/partner/transport')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Partner/Transport/Index')->has('offers.data', 4));

        $theirs = TravelOffer::where('owner_id', $other->id)->first();
        $this->actingAs($p)->get("/partner/transport/{$theirs->id}/editer")->assertForbidden();
        $this->actingAs($p)->delete("/partner/transport/{$theirs->id}")->assertForbidden();
    }

    public function test_promotion_applies_to_selected_trips_and_ends_automatically(): void
    {
        $p = $this->partner();
        $a = TravelOffer::create(['title' => 'A', 'type' => 'circuit', 'transport_mode' => 'bus', 'origin_city' => 'Brazzaville', 'destination_city' => 'Dolisie', 'destination_country' => 'CG', 'price' => 10000, 'currency' => 'XAF', 'seats_available' => 10, 'owner_id' => $p->id, 'departure_at' => now()->addDays(3), 'is_active' => true]);
        $b = TravelOffer::create(['title' => 'B', 'type' => 'circuit', 'transport_mode' => 'bus', 'origin_city' => 'Brazzaville', 'destination_city' => 'Oyo', 'destination_country' => 'CG', 'price' => 10000, 'currency' => 'XAF', 'seats_available' => 10, 'owner_id' => $p->id, 'departure_at' => now()->addDays(3), 'is_active' => true]);

        $this->actingAs($p)->post('/partner/promotions', [
            'offer_ids' => [$a->id], 'discount_percent' => 20, 'promo_label' => 'Promo de Noel', 'promo_ends_at' => now()->addDays(2)->toDateTimeString(),
        ])->assertSessionHas('success');
        $this->assertSame(20, $a->fresh()->discount_percent);
        $this->assertEquals(8000, $a->fresh()->discounted_price);
        $this->assertSame(0, $b->fresh()->discount_percent);

        $this->travelTo(now()->addDays(2)->addHour());
        $this->assertSame(0, $a->fresh()->discount_percent); // promotion terminee
        $this->assertEquals(10000, $a->fresh()->discounted_price);
    }

    public function test_bookings_and_passengers_are_listed_for_the_partner(): void
    {
        $p = $this->partner();
        $trip = TravelOffer::create(['title' => 'T', 'type' => 'circuit', 'transport_mode' => 'train', 'origin_city' => 'Brazzaville', 'destination_city' => 'Pointe-Noire', 'destination_country' => 'CG', 'price' => 12000, 'currency' => 'XAF', 'seats_available' => 10, 'owner_id' => $p->id, 'departure_at' => now()->addDays(3), 'is_active' => true]);
        $client = User::factory()->create(['role' => 'client', 'name' => 'Grace', 'phone' => '+242061112233']);
        Booking::create(['user_id' => $client->id, 'bookable_type' => TravelOffer::class, 'bookable_id' => $trip->id, 'quantity' => 2, 'unit_price' => 12000, 'total_amount' => 24000, 'currency' => 'XAF', 'status' => 'confirmed']);

        $this->actingAs($p)->get('/partner/reservations')->assertOk()->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)->where('bookings.data.0.client.name', 'Grace')->where('stats.month_revenue', 24000)->where('stats.month_seats', 2));
        $this->actingAs($p)->get("/partner/transport/{$trip->id}/passagers")->assertOk()->assertInertia(fn ($page) => $page->has('bookings', 1));
        $this->actingAs($p)->get('/partner')->assertOk()->assertInertia(fn ($page) => $page
            ->where('company.name', 'Ocean du Nord')->where('month.revenue', 24000)->has('nextDepartures', 1)->where('nextDepartures.0.sold', 2));

        // Un trajet avec des reservations est masque, pas supprime.
        $this->actingAs($p)->delete("/partner/transport/{$trip->id}");
        $this->assertNotNull($trip->fresh());
        $this->assertFalse($trip->fresh()->is_active);
    }

    public function test_airline_partner_publishes_flight_offer(): void
    {
        $p = $this->partner(['company_name' => 'Trans Air Congo', 'partner_activity' => 'compagnie_aerienne']);
        $this->actingAs($p)->post('/partner/transport', [
            'type' => 'vol', 'origin_city' => 'Brazzaville', 'destination_city' => 'Dubai', 'destination_country' => 'ae',
            'price' => 650000, 'seats_available' => 9, 'discount_percent' => 15, 'promo_label' => 'Fin d\'annee',
        ])->assertRedirect('/partner/transport?vue=vols');
        $o = TravelOffer::firstOrFail();
        $this->assertSame(['vol', 'avion', 'AE', 'Trans Air Congo'], [$o->type, $o->transport_mode, $o->destination_country, $o->airline]);
        $this->assertEquals(552500, $o->discounted_price);
    }
}
