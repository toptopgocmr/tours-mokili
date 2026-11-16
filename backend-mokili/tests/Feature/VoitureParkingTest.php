<?php

namespace Tests\Feature;

use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Voiture > Parking : parkings, gardiennage et lavage (hors Mecanique). */
class VoitureParkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_parking_tab_lists_parkings_washes_and_garages_offering_them(): void
    {
        $park = Business::create(['name' => 'Parking Centre', 'category' => 'parking', 'city' => 'Brazzaville', 'services' => ['Parking', 'Gardiennage'], 'is_active' => true]);
        $wash = Business::create(['name' => 'Garage Brillant', 'category' => 'garage', 'city' => 'Brazzaville', 'services' => ['Vidange', 'Lavage'], 'is_active' => true]);
        Business::create(['name' => 'Garage Freins', 'category' => 'garage', 'city' => 'Brazzaville', 'services' => ['Freinage'], 'is_active' => true]);

        $this->get('/voiture?type=parking')->assertOk()->assertInertia(fn ($p) => $p->component('Voiture/Index')
            ->where('offerTypes.parking', 'Parking')
            ->where('garages', fn ($g) => collect($g)->pluck('id')->contains($park->id) && collect($g)->pluck('id')->contains($wash->id) && ! collect($g)->pluck('name')->contains('Garage Freins'))
            ->where('parkingServices', fn ($s) => collect($s)->contains('Lavage') && collect($s)->contains('Gardiennage')));

        $this->get('/voiture?type=parking&q=Lavage')->assertOk()->assertInertia(fn ($p) => $p->where('garages', fn ($g) => collect($g)->pluck('id')->contains($wash->id) && ! collect($g)->pluck('id')->contains($park->id)));

        // Mecanique : lavage et gardiennage ne sont plus proposes, les parkings n'y apparaissent pas.
        $this->get('/voiture?type=mecanique')->assertOk()->assertInertia(fn ($p) => $p
            ->where('mechanicServices', fn ($s) => ! collect($s)->contains('Lavage') && ! collect($s)->contains('Gardiennage'))
            ->where('garages', fn ($g) => ! collect($g)->pluck('id')->contains($park->id)));
    }

    public function test_client_books_a_parking_with_parking_services(): void
    {
        $p = Business::create(['name' => 'Gardiennage Auto', 'category' => 'parking', 'city' => 'Brazzaville', 'services' => ['Gardiennage'], 'is_active' => true]);

        $this->get("/partenaires/{$p->slug}")->assertOk()->assertInertia(fn ($page) => $page
            ->where('mechanicServices', fn ($s) => collect($s)->contains('Gardiennage') && ! collect($s)->contains('Vidange')));

        $this->post("/partenaires/{$p->slug}/visite", [
            'name' => 'Jean Malonga', 'phone' => '+242 06 000 0000', 'visit_date' => now()->addDay()->toDateString(), 'service' => 'Gardiennage',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Gardiennage', VisitRequest::firstOrFail()->service);
    }

    public function test_mobile_api_parkings(): void
    {
        Business::create(['name' => 'Lavage Express', 'category' => 'parking', 'city' => 'Pointe-Noire', 'services' => ['Lavage'], 'is_active' => true]);

        $this->getJson('/api/voiture/parkings?city=Pointe-Noire')->assertOk()
            ->assertJsonPath('data.0.name', 'Lavage Express')
            ->assertJsonFragment(['services' => ['Parking', 'Gardiennage', 'Lavage', 'Nettoyage interieur', 'Lustrage / polissage']]);
    }
}
