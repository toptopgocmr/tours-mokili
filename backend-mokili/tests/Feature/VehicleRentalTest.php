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

    public function test_rental_options_and_sale_filters(): void
    {
        Vehicle::query()->delete(); // annonces de demonstration
        $car = $this->car();
        $van = $this->car(['title' => 'Hiace', 'category' => 'utilitaire']);
        $d = now()->addDays(3)->toDateString();
        $this->actingAs(User::factory()->create())->post("/voiture/{$car->slug}/reserver", [
            'starts_at' => $d, 'ends_at' => now()->addDays(5)->toDateString(), 'pickup_time' => '08:30', 'return_time' => '18:00',
            'pickup_mode' => 'aeroport', 'return_elsewhere' => true, 'return_place' => 'Pointe-Noire', 'driver_age' => '25-29',
        ])->assertRedirect();
        $b = Booking::latest('id')->first();
        $this->assertSame('Pointe-Noire', $b->meta['return_place']);
        $this->assertSame('25-29', $b->meta['driver_age']);
        $this->assertStringContainsString('18:00', (string) $b->ends_at);

        $this->get('/voiture?type=location&vehicule=utilitaires')->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.id', $van->id));

        $this->car(['title' => 'RAV4 a vendre', 'offer_type' => 'vente', 'price_per_day' => null, 'sale_price' => 9000000, 'year' => 2019, 'mileage_km' => 80000, 'fuel' => 'essence']);
        $this->car(['title' => 'Vieille Corolla', 'offer_type' => 'vente', 'price_per_day' => null, 'sale_price' => 3000000, 'year' => 2010, 'mileage_km' => 220000, 'fuel' => 'diesel']);
        $this->get('/voiture?type=vente&price_max=5000000')->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.title', 'Vieille Corolla'));
        $this->get('/voiture?type=vente&year_min=2015&km_max=100000')->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.title', 'RAV4 a vendre'));
        $this->get('/voiture?type=vente&sort=prix_asc')->assertInertia(fn ($p) => $p->where('vehicles.data.0.title', 'Vieille Corolla')->has('facets.fuels', 2));
    }

    public function test_seller_adds_sale_details_and_buyers_filter_them(): void
    {
        Vehicle::query()->delete();
        $seller = User::factory()->create(['role' => 'partner', 'partner_verified_at' => now()]);
        $this->actingAs($seller)->post('/partner/voiture', [
            'title' => 'Toyota RAV4 2019', 'offer_type' => 'vente', 'brand' => 'Toyota', 'model' => 'RAV4', 'year' => 2019,
            'category' => 'suv', 'transmission' => 'automatique', 'seats' => 5, 'sale_price' => 9500000, 'currency' => 'XAF',
            'mileage_km' => 82000, 'fuel' => 'essence', 'color' => 'blanc', 'doors' => 5, 'condition' => 'occasion',
            'first_owner' => true, 'engine' => '2.5 L', 'options' => ['GPS', 'Camera de recul'], 'is_active' => true, 'intent' => 'submit',
        ])->assertRedirect();
        $v = Vehicle::firstOrFail();
        $this->assertSame('blanc', $v->color);
        $this->assertTrue($v->first_owner);
        $this->assertSame(['GPS', 'Camera de recul'], $v->options);

        $v->update(['status' => 'published']);
        $this->car(['title' => 'Noire', 'offer_type' => 'vente', 'price_per_day' => null, 'sale_price' => 5000000, 'color' => 'noir', 'fuel' => 'diesel']);
        $this->get('/voiture?type=vente&color=blanc')->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.title', 'Toyota RAV4 2019'));
        $this->get('/voiture?type=vente&brand=Toyota&model=RAV4&first_owner=1&doors=5')->assertInertia(fn ($p) => $p->has('vehicles.data', 1));
        $this->get('/voiture?type=vente&fuel=diesel')->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.title', 'Noire'));
    }
}
