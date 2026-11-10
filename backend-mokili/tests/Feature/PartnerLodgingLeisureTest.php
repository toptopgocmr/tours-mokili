<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Loisirs\AttractionPark;
use App\Models\Logement\LodgingListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Espace partenaires : hotels/appartements, voitures, loisirs, parcs, promotions generiques. */
class PartnerLodgingLeisureTest extends TestCase
{
    use RefreshDatabase;

    protected function partner(array $a = []): User
    {
        return User::factory()->create($a + [
            'role' => 'partner', 'company_name' => 'Hotel Olympic', 'partner_activity' => 'hotel', 'partner_verified_at' => now(),
        ]);
    }

    protected function listing(User $owner, array $a = []): LodgingListing
    {
        return LodgingListing::create($a + [
            'owner_id' => $owner->id, 'title' => 'Suite '.uniqid(), 'slug' => 'suite-'.uniqid(), 'city' => 'Brazzaville',
            'country' => 'CG', 'price_per_night' => 50000, 'currency' => 'XAF', 'max_guests' => 2, 'is_active' => true,
        ]);
    }

    public function test_partner_registers_as_hotel_or_cinema(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (['hotel', 'appartement', 'location_voiture', 'vente_voiture', 'cinema', 'parc_loisirs'] as $i => $act) {
            $this->post('/devenir-partenaire', [
                'company_name' => "Societe $i", 'partner_activity' => $act, 'name' => 'Jean', 'email' => "p$i@example.com",
                'phone' => '+24206000000'.$i, 'password' => 'secret123', 'password_confirmation' => 'secret123', 'accept' => true,
            ])->assertRedirect('/partner');
            auth()->logout();

            $this->assertSame($act, User::where('email', "p$i@example.com")->value('partner_activity'));
        }
    }

    public function test_partner_manages_his_own_park_only(): void
    {
        $p = $this->partner(['partner_activity' => 'parc_loisirs', 'partner_verified_at' => null]);
        $this->actingAs($p)->post('/partner/parcs', [
            'name' => 'Mami Wata Park', 'category' => 'parc_aquatique', 'city' => 'Pointe-Noire', 'currency' => 'XAF',
            'price_adult' => 5000, 'price_child' => 2500, 'is_active' => true, 'is_featured' => true,
        ])->assertRedirect();
        $park = AttractionPark::where('name', 'Mami Wata Park')->firstOrFail();
        $this->assertSame($p->id, $park->owner_id);
        $this->assertFalse($park->is_active);   // partenaire pas encore valide
        $this->assertFalse((bool) $park->is_featured); // reserve a l'admin

        $this->get('/partner/parcs')->assertOk()->assertSee('Mami Wata Park');

        $other = $this->partner(['email' => 'autre@t.cg']);
        $this->actingAs($other)->get("/partner/parcs/{$park->id}/editer")->assertForbidden();
        $this->actingAs($other)->delete("/partner/parcs/{$park->id}")->assertForbidden();
        $this->assertNotNull($park->fresh());
    }

    public function test_promotion_on_lodging_changes_booking_price_and_expires(): void
    {
        $p = $this->partner();
        $mine = $this->listing($p);
        $notMine = $this->listing($this->partner(['email' => 'b@t.cg']));

        $this->actingAs($p)->post('/partner/promotions', [
            'items' => ['logements' => [$mine->id, $notMine->id]],
            'discount_percent' => 20, 'promo_label' => 'Week-end', 'promo_ends_at' => now()->addWeek()->format('Y-m-d H:i'),
        ])->assertRedirect();

        $this->assertSame(20, $mine->fresh()->discount_percent);
        $this->assertSame(0, (int) $notMine->fresh()->discount_percent); // pas a lui
        $this->assertEquals(40000, $mine->fresh()->final_price_per_night);

        $client = User::factory()->create();
        $this->actingAs($client)->post("/logement/{$mine->slug}/reserver", [
            'starts_at' => now()->addDays(3)->toDateString(), 'ends_at' => now()->addDays(5)->toDateString(), 'guests' => 2,
        ])->assertRedirect();
        $b = Booking::firstOrFail();
        $this->assertEquals(40000, $b->unit_price);
        $this->assertEquals(80000, $b->total_amount);

        // Apres la date de fin, le prix normal revient tout seul.
        $this->travel(8)->days();
        $this->assertSame(0, $mine->fresh()->discount_percent);
        $this->assertEquals(50000, $mine->fresh()->final_price_per_night);
    }

    public function test_partner_sees_lodging_and_park_bookings_in_one_list(): void
    {
        $p = $this->partner();
        $l = $this->listing($p, ['title' => 'Chambre Fleuve']);
        $park = AttractionPark::create(['owner_id' => $p->id, 'name' => 'Parc Kintele', 'slug' => 'parc-kintele', 'category' => 'parc_attractions', 'city' => 'Brazzaville', 'country' => 'CG', 'currency' => 'XAF', 'price_adult' => 3000, 'is_active' => true]);
        $foreign = $this->listing($this->partner(['email' => 'c@t.cg']), ['title' => 'Chambre Concurrent']);
        $client = User::factory()->create();
        foreach ([[LodgingListing::class, $l->id], [AttractionPark::class, $park->id], [LodgingListing::class, $foreign->id]] as [$type, $id]) {
            Booking::create(['user_id' => $client->id, 'bookable_type' => $type, 'bookable_id' => $id, 'quantity' => 1, 'unit_price' => 1000, 'total_amount' => 1000, 'currency' => 'XAF', 'status' => 'confirmed', 'starts_at' => now()->addDay()]);
        }

        $this->actingAs($p)->get('/partner/reservations')->assertOk()
            ->assertSee('Chambre Fleuve')->assertSee('Parc Kintele')->assertDontSee('Chambre Concurrent');
        $this->get('/partner/promotions')->assertOk()->assertSee('Chambre Fleuve')->assertSee('Parc Kintele');
        $this->get('/partner')->assertOk();
    }
}
