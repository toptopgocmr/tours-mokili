<?php

namespace Tests\Feature;

use App\Models\Loisirs\AttractionPark;
use App\Models\ServiceCategory;
use App\Models\Voyage\TravelOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DivertissementAndCircuitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_loisirs_tile_is_removed_and_parks_live_in_divertissement(): void
    {
        $this->seed(\Database\Seeders\ServiceCategorySeeder::class);
        $this->assertFalse(ServiceCategory::where('slug', 'loisirs')->exists());

        $park = AttractionPark::query()->active()->firstOrFail(); // catalogue seme par migration

        // Ancienne adresse ?category=parcs -> onglet Parcs de Tourisme.
        $this->get('/divertissement?category=parcs')->assertOk()
            ->assertInertia(fn ($page) => $page->component('Divertissement/Index')->where('tab', 'parcs')->where('cards.data.0.tab', 'parcs')->has('cards.data.0.href'));

        $this->get('/divertissement')->assertOk()
            ->assertInertia(fn ($page) => $page->where('tab', null)->where('counts.parcs', AttractionPark::query()->active()->count())->has('tabs.visite'));

        $this->get("/divertissement/parcs/{$park->slug}")->assertOk()
            ->assertInertia(fn ($page) => $page->component('Loisirs/Show'));

        $this->get('/loisirs')->assertRedirect('/divertissement?tab=parcs');
        $this->get("/loisirs/{$park->slug}")->assertRedirect("/divertissement/parcs/{$park->slug}");
    }

    public function test_merge_migration_removes_existing_loisirs_category(): void
    {
        DB::table('service_categories')->insert(['slug' => 'loisirs', 'name' => 'Loisirs & Parcs', 'position' => 7, 'created_at' => now(), 'updated_at' => now()]);
        (require database_path('migrations/2026_11_03_000004_merge_loisirs_into_divertissement.php'))->up();
        $this->assertFalse(DB::table('service_categories')->where('slug', 'loisirs')->exists());
    }

    public function test_circuits_can_be_filtered_by_transport_mode(): void
    {
        $make = fn ($title, $mode) => TravelOffer::create([
            'title' => $title, 'type' => 'circuit', 'transport_mode' => $mode, 'destination_city' => 'Pointe-Noire',
            'destination_country' => 'CG', 'price' => 25000, 'currency' => 'XAF', 'seats_available' => 30,
        ]);
        $make('Brazza - Pointe-Noire en bus', 'bus');
        $make('Brazza - Pointe-Noire en train', 'train');
        $make('Croisiere sur le fleuve', 'bateau');

        $this->get('/voyage?type=circuit&mode=train')->assertOk()
            ->assertInertia(fn ($page) => $page->has('offers.data', 1)
                ->where('offers.data.0.transport_mode_label', 'Train')
                ->has('transportModes.bateau'));

        $this->getJson('/api/voyage/offers?type=circuit&mode=bateau')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/voyage/transport-modes')->assertOk()->assertJsonFragment(['key' => 'bus']);
    }

    public function test_admin_must_choose_a_transport_mode_for_circuits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'title' => 'Circuit Plateaux Bateke', 'type' => 'circuit', 'destination_city' => 'Lefini',
            'destination_country' => 'CG', 'price' => 150000, 'currency' => 'XAF', 'seats_available' => 8,
        ];

        $this->actingAs($admin)->post('/admin/voyage', $payload)->assertSessionHasErrors('transport_mode');
        $this->actingAs($admin)->post('/admin/voyage', $payload + ['transport_mode' => 'voiture', 'duration_label' => '2 jours'])
            ->assertRedirect('/admin/voyage');
        $this->assertDatabaseHas('travel_offers', ['title' => 'Circuit Plateaux Bateke', 'transport_mode' => 'voiture']);
    }
}
