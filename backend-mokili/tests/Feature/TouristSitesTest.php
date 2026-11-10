<?php

namespace Tests\Feature;

use App\Models\Guide\Business;
use App\Models\Guide\VisitRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TouristSitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visite_tab_lists_tourist_sites_with_photos(): void
    {
        $this->get('/divertissement?tab=visite')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Divertissement/Index')
                ->where('cards.total', fn ($n) => $n >= 15)
                ->where('cards.data.0.image', fn ($u) => str_starts_with((string) $u, 'https://')));
    }

    public function test_visitor_can_request_a_visit(): void
    {
        $site = Business::query()->where('category', 'site_touristique')->firstOrFail();

        $this->get('/partenaires/'.$site->slug)->assertOk()
            ->assertInertia(fn ($p) => $p->component('Partners/Show')->has('formulas'));

        $this->post('/partenaires/'.$site->slug.'/visite', [
            'name' => 'Jean Malonga', 'phone' => '06 123 45 67', 'visit_date' => now()->addDays(3)->toDateString(),
            'people' => 4, 'formula' => 'groupe', 'message' => 'Avec guide',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('visit_requests', ['business_id' => $site->id, 'people' => 4, 'status' => 'nouvelle']);

        $this->post('/partenaires/'.$site->slug.'/visite', ['name' => 'X', 'phone' => '06', 'visit_date' => now()->subDay()->toDateString(), 'people' => 0, 'formula' => 'x'])
            ->assertSessionHasErrors(['phone', 'visit_date', 'people', 'formula']);
    }

    public function test_admin_sees_and_updates_visit_requests(): void
    {
        $site = Business::query()->where('category', 'site_touristique')->firstOrFail();
        $req = VisitRequest::create(['business_id' => $site->id, 'name' => 'A', 'phone' => '061234567', 'visit_date' => now()->addDay(), 'people' => 2, 'formula' => 'individuel']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/visites')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Visites/Index')->where('requests.total', 1));
        $this->actingAs($admin)->patch('/admin/visites/'.$req->id, ['status' => 'confirmee'])->assertRedirect();
        $this->assertSame('confirmee', $req->fresh()->status);
    }
}
