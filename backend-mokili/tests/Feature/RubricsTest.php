<?php

namespace Tests\Feature;

use App\Models\Divertissement\Event;
use App\Models\Fret\FreightOffer;
use App\Models\Guide\Business;
use App\Models\Logement\LodgingListing;
use App\Models\Marketplace\Product;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Voiture\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Filtres par rubrique : Logement, Voiture, Tourisme, Marketplace, Fret, circuits. */
class RubricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logement_filters_villa_hotel_appartement(): void
    {
        LodgingListing::query()->delete(); // logements de demonstration
        foreach (['villa', 'hotel', 'appartement', 'villa'] as $i => $type) {
            LodgingListing::create(['title' => "Logement $i", 'property_type' => $type, 'city' => 'Brazzaville', 'country' => 'CG',
                'price_per_night' => 30000, 'status' => 'published', 'is_active' => true]);
        }

        $this->get('/logement?type=villa')->assertOk()
            ->assertInertia(fn ($p) => $p->has('listings.data', 2)->where('listings.data.0.property_type_label', 'Villa')->has('propertyTypes.hotel'));
        $this->getJson('/api/logement/listings?property_type=hotel')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_voiture_location_vente_mecanique(): void
    {
        Vehicle::query()->delete(); // catalogue d'exemple
        $base = ['brand' => 'Toyota', 'model' => 'RAV4', 'city' => 'Brazzaville', 'status' => 'published', 'is_active' => true];
        Vehicle::create($base + ['title' => 'RAV4 a louer', 'offer_type' => 'location', 'price_per_day' => 45000]);
        Vehicle::create($base + ['title' => 'RAV4 2018 a vendre', 'offer_type' => 'vente', 'sale_price' => 9500000, 'mileage_km' => 80000]);
        Vehicle::create($base + ['title' => 'Vidange toutes marques', 'offer_type' => 'mecanique', 'sale_price' => 15000, 'services' => ['Vidange']]);
        Business::create(['name' => 'Garage du Plateau', 'category' => 'garage', 'city' => 'Brazzaville']);

        // Les vehicules de demonstration (migrations) sont tous en location.
        $this->get('/voiture?type=vente')->assertOk()
            ->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->where('vehicles.data.0.display_price.amount', 9500000));
        $this->get('/voiture?type=mecanique')->assertOk()
            ->assertInertia(fn ($p) => $p->has('vehicles.data', 1)->has('garages', 1));
        $res = $this->getJson('/api/voiture/vehicles?offer_type=location')->assertOk();
        $this->assertNotEmpty($res->json('data'));
        $this->assertSame(['location'], array_values(array_unique(array_column($res->json('data'), 'offer_type'))));
        $this->assertNotContains('RAV4 2018 a vendre', array_column($res->json('data'), 'title'));
    }

    public function test_partner_must_choose_offer_type_and_price(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $payload = ['title' => 'Prado', 'offer_type' => 'vente', 'brand' => 'Toyota', 'model' => 'Prado', 'category' => 'suv',
            'transmission' => 'automatique', 'seats' => 7, 'currency' => 'XAF', 'intent' => 'submit'];

        $this->actingAs($partner)->post('/partner/voiture', $payload)->assertSessionHasErrors('sale_price');
        $this->actingAs($partner)->post('/partner/voiture', $payload + ['sale_price' => 25000000])->assertRedirect('/partner/voiture');
        $this->assertDatabaseHas('vehicles', ['title' => 'Prado', 'offer_type' => 'vente', 'price_per_day' => null]);
    }

    public function test_tourisme_tabs_read_events_parks_and_places(): void
    {
        $event = fn ($title, $cat) => Event::create(['title' => $title, 'category' => $cat, 'city' => 'Brazzaville', 'starts_at' => now()->addDays(5),
            'price' => 5000, 'status' => 'published', 'is_active' => true]);
        $event('Concert Fally', 'concert');
        $event('Theatre', 'spectacle');
        $event('Avant-premiere', 'cinema');
        $event('Match', 'sport');
        Business::create(['name' => 'Le Diplomate', 'category' => 'night_club', 'city' => 'Brazzaville']);
        Business::create(['name' => 'Basilique Sainte-Anne', 'category' => 'site_touristique', 'city' => 'Brazzaville']);

        $expected = fn (array $cats) => Event::query()->active()->where('starts_at', '>=', now()->subHours(6))->whereIn('category', $cats)->count();
        $this->get('/divertissement?tab=concerts')->assertOk()->assertInertia(fn ($p) => $p->where('source', 'events')
            ->has('items.data', min(12, $expected(['concert', 'spectacle'])))
            ->where('items.data', fn ($items) => collect($items)->every(fn ($e) => in_array($e['category'], ['concert', 'spectacle'], true))));
        $this->get('/divertissement?tab=cinema')->assertOk()->assertInertia(fn ($p) => $p->has('items.data', min(12, $expected(['cinema']))));
        $this->get('/divertissement?tab=night_club')->assertOk()->assertInertia(fn ($p) => $p->where('source', 'businesses')->where('items.data.0.name', 'Le Diplomate'));
        $this->get('/divertissement?tab=visite')->assertOk()->assertInertia(fn ($p) => $p->has('items.data', 1));
        $this->getJson('/api/tourisme?tab=parcs')->assertOk()->assertJsonPath('source', 'parks');
        $this->get('/tourisme?tab=cinema')->assertRedirect('/divertissement?tab=cinema');
    }

    public function test_service_tile_is_renamed_tourisme(): void
    {
        $this->seed(\Database\Seeders\ServiceCategorySeeder::class);
        $this->get('/')->assertInertia(fn ($p) => $p->where('categories.3.name', 'Tourisme')->has('categories', 6));
    }

    public function test_marketplace_classifieds_flow(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($seller)->post('/marketplace/deposer', [
            'listing_type' => 'vente', 'title' => 'iPhone 13 128 Go', 'category' => 'telephones',
            'description' => 'Tres bon etat, batterie 90 %, avec chargeur.', 'price' => 320000, 'negotiable' => true,
            'condition' => 'occasion', 'city' => 'Brazzaville', 'district' => 'Moungali', 'contact_phone' => '064445566',
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertRedirect('/marketplace/mes-annonces');

        $product = Product::where('title', 'iPhone 13 128 Go')->firstOrFail();
        $this->assertSame('pending', $product->status);
        $this->assertCount(2, $product->gallery);
        Storage::disk('public')->assertExists($product->gallery[0]);

        // Invisible avant validation, visible ensuite.
        $titles = fn ($res) => collect($res->viewData('page')['props']['products']['data'])->pluck('title');
        $this->assertNotContains('iPhone 13 128 Go', $titles($this->get('/marketplace')));
        $this->actingAs($admin)->post("/admin/moderation/marketplace/{$product->id}/approuver")->assertSessionHas('success');
        auth()->logout();
        $this->assertContains('iPhone 13 128 Go', $titles($this->get('/marketplace?category=telephones&city=brazza')));
        $this->assertNotContains('iPhone 13 128 Go', $titles($this->get('/marketplace?category=vehicules')));
        $this->get("/marketplace/{$product->slug}")->assertOk()->assertInertia(fn ($p) => $p->where('product.contact_phone', '064445566'));
        $this->assertSame(1, $product->fresh()->views);

        // Un autre client ne peut pas modifier l'annonce.
        $other = User::factory()->create(['role' => 'client']);
        $this->actingAs($other)->delete("/marketplace/annonce/{$product->id}")->assertForbidden();

        $this->actingAs($seller)->post("/marketplace/annonce/{$product->id}/vendu")->assertSessionHas('success');
        $this->assertNotNull($product->fresh()->sold_at);
        auth()->logout();
        $this->assertNotContains('iPhone 13 128 Go', $titles($this->get('/marketplace')));
    }

    public function test_marketplace_requires_login_and_valid_fields(): void
    {
        $this->get('/marketplace/deposer')->assertRedirect('/login');
        $user = User::factory()->create(['role' => 'client']);
        $this->actingAs($user)->post('/marketplace/deposer', ['title' => 'x'])->assertSessionHasErrors(['category', 'description', 'contact_phone', 'city']);
    }

    public function test_fret_filters_and_package_type(): void
    {
        $offer = fn ($title, $mode, $from, $to, $types) => FreightOffer::create(['title' => $title, 'mode' => $mode, 'origin_city' => 'X', 'origin_country' => $from,
            'destination_city' => 'Y', 'destination_country' => $to, 'price_per_kg' => 1000, 'package_types' => $types, 'status' => 'published', 'is_active' => true]);
        $offer('Brazza - Pointe-Noire', 'route', 'CG', 'CG', ['marchandise', 'vehicule']);
        $offer('Chine - Congo conteneurs', 'mer', 'CN', 'CG', ['conteneur', 'vehicule']);
        $sea = $offer('Paris - Brazza express', 'air', 'FR', 'CG', ['documents', 'marchandise']);

        FreightOffer::whereNotIn('title', ['Brazza - Pointe-Noire', 'Chine - Congo conteneurs', 'Paris - Brazza express'])->delete(); // offres de demo
        $this->get('/fret?scope=national')->assertInertia(fn ($p) => $p->has('offers.data', 1));
        $this->get('/fret?scope=international&colis=vehicule')->assertInertia(fn ($p) => $p->has('offers.data', 1)->where('offers.data.0.mode', 'mer'));
        $this->get('/fret?mode=air')->assertInertia(fn ($p) => $p->has('offers.data', 1));
        $this->getJson('/api/fret/offers?package_type=documents')->assertOk()->assertJsonCount(1, 'data');

        $user = User::factory()->create(['role' => 'client']);
        $this->actingAs($user)->post("/fret/{$sea->slug}/demande", ['weight_kg' => 2, 'package_type' => 'vehicule'])->assertSessionHasErrors('package_type');
        $this->actingAs($user)->post("/fret/{$sea->slug}/demande", ['weight_kg' => 2, 'package_type' => 'documents', 'package_description' => 'Diplomes'])->assertRedirect();
        $this->assertDatabaseHas('freight_shipments', ['package_type' => 'documents', 'package_description' => 'Diplomes']);
    }

    public function test_circuits_filter_by_scope_and_department(): void
    {
        $make = fn ($t, $mode, $scope, $from, $to) => \App\Models\Voyage\TravelOffer::create(['title' => $t, 'type' => 'circuit', 'transport_mode' => $mode,
            'trip_scope' => $scope, 'origin_department' => $from, 'destination_department' => $to, 'destination_city' => 'X', 'destination_country' => 'CG',
            'price' => 10000, 'currency' => 'XAF', 'seats_available' => 10]);
        $make('Tour de Brazzaville', 'voiture', 'urbain', 'Brazzaville', 'Brazzaville');
        $make('Brazza - Ouesso', 'bus', 'interdepartemental', 'Brazzaville', 'Sangha');
        $make('Pirogue Lefini', 'canot', 'interdepartemental', 'Djoue-Lefini', 'Djoue-Lefini');

        $this->get('/voyage?type=circuit&scope=interdepartemental')->assertInertia(fn ($p) => $p->has('offers.data', 2)->has('departments', 15));
        $this->get('/voyage?type=circuit&departement=Sangha')->assertInertia(fn ($p) => $p->has('offers.data', 1));
        $this->get('/voyage?type=circuit&mode=canot')->assertInertia(fn ($p) => $p->has('offers.data', 1)->where('offers.data.0.transport_mode_label', 'Canot rapide / pirogue'));
        $this->getJson('/api/catalogue')->assertOk()->assertJsonPath('voiture.offer_types.mecanique', 'Mecanique');
    }
}
