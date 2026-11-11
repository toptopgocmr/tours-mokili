<?php

namespace Tests\Feature;

use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use App\Models\Voiture\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Voiture : garages par prestation, rendez-vous garage, photo d'illustration et vendeurs par marque. */
class VoitureGaragesAndSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mechanic_page_filters_garages_by_service_and_lists_services(): void
    {
        $tow = Business::create(['name' => 'Depann Express', 'category' => 'garage', 'city' => 'Brazzaville', 'services' => ['Depannage / remorquage'], 'is_active' => true]);
        Business::create(['name' => 'Garage Vidange', 'category' => 'garage', 'city' => 'Pointe-Noire', 'services' => ['Vidange'], 'is_active' => true]);

        $this->get('/voiture?type=mecanique&q='.urlencode('Depannage / remorquage'))->assertOk()
            ->assertInertia(fn ($p) => $p->component('Voiture/Index')
                ->has('garages', 1)->where('garages.0.id', $tow->id)
                ->where('mechanicServices', fn ($s) => collect($s)->contains('Depannage / remorquage')));

        $this->get('/voiture?type=mecanique&city=Pointe-Noire')->assertOk()
            ->assertInertia(fn ($p) => $p->where('garages', fn ($g) => collect($g)->pluck('city')->unique()->values()->all() === ['Pointe-Noire']
                && collect($g)->pluck('name')->contains('Garage Vidange')));
    }

    public function test_client_books_a_garage_appointment(): void
    {
        $g = Business::create(['name' => 'Garage RDV', 'category' => 'garage', 'city' => 'Brazzaville', 'services' => ['Vidange'], 'is_active' => true]);

        $this->post("/partenaires/{$g->slug}/visite", [])->assertSessionHasErrors(['name', 'phone', 'visit_date', 'service']);

        $this->post("/partenaires/{$g->slug}/visite", [
            'name' => 'Jean Malonga', 'phone' => '+242 06 000 0000', 'visit_date' => now()->addDay()->toDateString(),
            'visit_time' => '09:30', 'service' => 'Vidange', 'vehicle' => 'Toyota Corolla 2015',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $r = VisitRequest::firstOrFail();
        $this->assertSame(['Vidange', 'Toyota Corolla 2015', '09:30', 1], [$r->service, $r->vehicle, $r->visit_time, $r->people]);
    }

    public function test_listing_without_photo_gets_brand_photo_and_brand_dealers(): void
    {
        $v = Vehicle::create(['title' => 'Jetour T2 2025', 'offer_type' => 'vente', 'brand' => 'Jetour', 'model' => 'T2 i-DM', 'sale_price' => 20000000,
            'currency' => 'XAF', 'city' => 'Brazzaville', 'country' => 'CG', 'is_active' => true, 'status' => 'published']);
        $this->assertStringContainsString('Jetour_Traveller', $v->image_url);
        $this->assertTrue($v->image_is_illustration);

        Business::create(['name' => 'Toyota Dealer', 'category' => 'concessionnaire', 'city' => 'Brazzaville', 'services' => ['Toyota'], 'is_active' => true]);
        Business::create(['name' => 'Auto Multi', 'category' => 'concessionnaire', 'city' => 'Brazzaville', 'services' => ['Multimarque'], 'is_active' => true]);

        $this->get("/voiture/{$v->slug}")->assertOk()
            ->assertInertia(fn ($p) => $p->has('dealers', 1)->where('dealers.0.name', 'Auto Multi'));
    }

    public function test_mobile_api_garages_meta_dealers_and_booking(): void
    {
        $g = Business::create(['name' => 'Garage API', 'category' => 'garage', 'city' => 'Pointe-Noire', 'services' => ['Diagnostic'], 'is_active' => true]);
        Business::create(['name' => 'Multi API', 'category' => 'concessionnaire', 'city' => 'Brazzaville', 'services' => ['Multimarque'], 'is_active' => true]);
        $v = Vehicle::create(['title' => 'Haval H6 2023', 'offer_type' => 'vente', 'brand' => 'Haval', 'model' => 'H6', 'sale_price' => 15000000,
            'currency' => 'XAF', 'city' => 'Brazzaville', 'country' => 'CG', 'is_active' => true, 'status' => 'published']);

        $this->getJson('/api/voiture/garages?q=Diagnostic&city=Pointe-Noire')->assertOk()
            ->assertJsonPath('data.0.id', $g->id)->assertJsonFragment(['Depannage / remorquage']);
        $this->getJson('/api/voiture/meta')->assertOk()->assertJsonFragment(['key' => 'Haval', 'count' => 1]);
        $this->getJson('/api/voiture/vehicles?offer_type=vente&brand=Haval')->assertOk()->assertJsonPath('data.0.id', $v->id)
            ->assertJsonPath('data.0.image_is_illustration', true);
        $this->getJson("/api/voiture/vehicles/{$v->slug}")->assertOk()->assertJsonPath('dealers.0.name', 'Multi API');

        $this->getJson("/api/partners/{$g->slug}")->assertOk()->assertJsonPath('booking', 'rdv');
        $this->postJson("/api/partners/{$g->slug}/booking", ['name' => 'Ana', 'phone' => '+242 06 111 2233',
            'visit_date' => now()->addDays(2)->toDateString(), 'service' => 'Diagnostic'])->assertCreated();
        $this->assertSame('Diagnostic', VisitRequest::first()->service);
    }
}
