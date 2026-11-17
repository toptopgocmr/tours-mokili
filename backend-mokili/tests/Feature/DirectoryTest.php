<?php

namespace Tests\Feature;

use App\Models\Guide\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** ANNUAIRE : recherche, filtres, inscription gratuite et validation admin. */
class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Business::query()->delete(); // les migrations installent les societes de demo
    }

    private function make(array $attrs): Business
    {
        return Business::create($attrs + ['city' => 'Brazzaville', 'country' => 'CG', 'is_active' => true]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'name' => 'Banque Test Abidjan', 'category' => 'banque', 'country' => 'CI', 'city' => 'Abidjan',
            'district' => 'Plateau', 'address' => 'Boulevard de la Republique', 'phone' => '+225 01 02 03 04',
            'email' => 'contact@banque-test.ci', 'contact_person' => 'Awa K., directrice', 'accept' => true,
        ];
    }

    public function test_directory_lists_all_rubrics_and_filters(): void
    {
        $this->make(['name' => 'Hotel Olympic', 'category' => 'hotel']);
        $this->make(['name' => 'Garage Central', 'category' => 'garage', 'city' => 'Pointe-Noire']);
        $this->make(['name' => 'Banque de Kinshasa', 'category' => 'banque', 'country' => 'CD', 'city' => 'Kinshasa']);
        $this->make(['name' => 'Masquee SA', 'category' => 'banque', 'is_active' => false]);

        $this->get('/annuaire')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Annuaire/Index')
            ->where('total', 3)
            ->has('businesses.data', 3)
            ->where('businesses.data.0.country_name', fn ($v) => is_string($v)));

        $this->get('/annuaire?domaine=banque')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 1)->where('businesses.data.0.name', 'Banque de Kinshasa'));
        $this->get('/annuaire?pays=cg')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 2)->where('cities', ['Brazzaville', 'Pointe-Noire']));
        $this->get('/annuaire?pays=CG&ville=Pointe-Noire')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 1)->where('businesses.data.0.name', 'Garage Central'));
        $this->get('/annuaire?q=olymp')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 1));
        // Valeurs inconnues ignorees.
        $this->get('/annuaire?pays=ZZ&domaine=xyz')->assertInertia(fn (Assert $p) => $p->where('filters.pays', '')->where('filters.domaine', ''));
    }

    public function test_company_registers_and_is_hidden_until_admin_validates(): void
    {
        $this->get('/annuaire/inscription')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Annuaire/Register')->has('domains')->has('countries'));

        $this->post('/annuaire/inscription', $this->payload())->assertRedirect('/annuaire')->assertSessionHas('success');

        $b = Business::where('name', 'Banque Test Abidjan')->firstOrFail();
        $this->assertFalse($b->is_active);
        $this->assertNotNull($b->submitted_at);
        $this->assertSame('CI', $b->country);
        $this->assertSame("Cote d'Ivoire", $b->country_name);
        $this->get('/annuaire')->assertInertia(fn (Assert $p) => $p->where('total', 0));
        $this->get("/partenaires/{$b->slug}")->assertNotFound();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/partenaires?pending=1')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('pendingCount', 1)->has('businesses.data', 1));

        // L'admin publie la fiche : le pays n'est plus force au Congo.
        $this->put("/admin/partenaires/{$b->id}", [
            'name' => $b->name, 'category' => 'banque', 'city' => 'Abidjan', 'country' => 'CI', 'is_active' => true,
        ])->assertRedirect();
        $b->refresh();
        $this->assertTrue($b->is_active);
        $this->assertSame('CI', $b->country);

        $this->get('/annuaire?pays=CI')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 1));
        $this->get("/partenaires/{$b->slug}")->assertOk();
    }

    public function test_registration_validation_and_honeypot(): void
    {
        $this->post('/annuaire/inscription', $this->payload(['phone' => '', 'category' => 'inconnu', 'accept' => false]))
            ->assertSessionHasErrors(['phone', 'category', 'accept']);

        $this->post('/annuaire/inscription', $this->payload(['fax' => 'robot']))->assertRedirect('/annuaire');
        $this->assertSame(0, Business::count());
    }

    public function test_mobile_api(): void
    {
        $this->make(['name' => 'Pharmacie Mavre', 'category' => 'sante']);

        $this->getJson('/api/annuaire?domaine=sante')->assertOk()
            ->assertJsonPath('businesses.data.0.name', 'Pharmacie Mavre')
            ->assertJsonStructure(['domains', 'countries', 'cities', 'filters']);

        $this->postJson('/api/annuaire/inscription', $this->payload())->assertCreated();
        $this->assertDatabaseHas('businesses', ['name' => 'Banque Test Abidjan', 'is_active' => false]);
    }

    public function test_directory_categories_stay_out_of_rubrics(): void
    {
        $this->make(['name' => 'Hotel A', 'category' => 'hotel']);
        $this->make(['name' => 'Assureur B', 'category' => 'assurance']);

        $this->getJson('/api/partners?module=logement')->assertOk()->assertJsonCount(1, 'data');
        $this->assertNull(Business::where('category', 'assurance')->first()->module);
    }
}
