<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Voiture\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Location de voiture : disponibilite, reservation, double reservation impossible. */
class VehicleRentalTest extends TestCase
{
    use RefreshDatabase;

    protected function car(array $a = []): Vehicle
    {
        return Vehicle::create($a + ['title' => 'Toyota Test', 'offer_type' => 'location', 'brand' => 'Toyota', 'model' => 'Corolla', 'price_per_day' => 25000,
            'currency' => 'XAF', 'city' => 'Brazzaville', 'country' => 'CG', 'transmission' => 'automatique', 'seats' => 5, 'is_active' => true, 'status' => 'published']);
    }

    public function test_client_books_and_dates_become_unavailable(): void
    {
        $car = $this->car();
        $start = now()->addDays(5)->toDateString();
        $end = now()->addDays(8)->toDateString();

        $this->getJson("/voiture/{$car->slug}/disponibilite?start=$start&end=$end")->assertOk()
            ->assertJson(['available' => true, 'days' => 3, 'total' => 75000]);

        $client = User::factory()->create();
        $this->actingAs($client)->post("/voiture/{$car->slug}/reserver", ['starts_at' => $start, 'ends_at' => $end, 'pickup_time' => '10:00', 'pickup_mode' => 'agence'])
            ->assertRedirect();
        $b = Booking::where('bookable_type', Vehicle::class)->firstOrFail();
        $this->assertSame(3, (int) $b->quantity);
        $this->assertEquals(75000, $b->total_amount);
        $this->assertSame('awaiting_payment', $b->status);

        // Chevauchement : refuse + prochaine date proposee.
        $mid = now()->addDays(7)->toDateString();
        $this->getJson("/voiture/{$car->slug}/disponibilite?start=$mid&end=".now()->addDays(10)->toDateString())->assertOk()
            ->assertJson(['available' => false])->assertJsonPath('next_free', fn ($d) => $d > $end);
        $this->actingAs(User::factory()->create())->post("/voiture/{$car->slug}/reserver", ['starts_at' => $mid, 'ends_at' => $mid, 'pickup_mode' => 'agence'])
            ->assertSessionHasErrors('starts_at');

        // Liste : le vehicule disparait du filtre "disponible du ... au ...".
        $this->get("/voiture?type=location&start=$start&end=$end")->assertInertia(fn ($p) => $p->where('vehicles.data', fn ($d) => collect($d)->doesntContain('id', $car->id)));
        $this->get('/voiture/'.$car->slug)->assertInertia(fn ($p) => $p->where('busyDays', fn ($d) => collect($d)->contains($start)));
    }

    public function test_unpaid_hold_expires_and_delivery_needs_address(): void
    {
        $car = $this->car();
        $start = now()->addDays(2)->toDateString();
        Booking::create(['user_id' => User::factory()->create()->id, 'bookable_type' => Vehicle::class, 'bookable_id' => $car->id, 'quantity' => 1, 'unit_price' => 1, 'total_amount' => 1, 'currency' => 'XAF', 'status' => 'awaiting_payment', 'starts_at' => $start, 'ends_at' => $start]);
        $this->getJson("/voiture/{$car->slug}/disponibilite?start=$start&end=$start")->assertJson(['available' => false]);
        $this->travel(31)->minutes();
        $this->getJson("/voiture/{$car->slug}/disponibilite?start=$start&end=$start")->assertJson(['available' => true]);

        $this->actingAs(User::factory()->create())->post("/voiture/{$car->slug}/reserver", ['starts_at' => $start, 'ends_at' => $start, 'pickup_mode' => 'livraison'])
            ->assertSessionHasErrors('pickup_address');
    }
}
