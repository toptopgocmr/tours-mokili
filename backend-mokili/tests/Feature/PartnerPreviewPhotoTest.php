<?php

namespace Tests\Feature;

use App\Models\Guide\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mode presentation : la photo apercu n'est visible que par l'equipe. */
class PartnerPreviewPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_photo_is_staff_only_until_published(): void
    {
        $b = Business::create(['name' => 'Hotel Test', 'category' => 'hotel', 'city' => 'Brazzaville', 'country' => 'CG', 'is_active' => true, 'preview_image' => 'https://example.com/hotel.jpg']);

        $this->getJson('/api/partners?module=logement')->assertOk()->assertDontSee('example.com/hotel.jpg');
        $this->actingAs(User::factory()->create())->get('/admin/partenaires/apercus')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson('/admin/partenaires/apercus')->assertOk()->assertJsonFragment([(string) $b->id => 'https://example.com/hotel.jpg']);

        $this->put("/admin/partenaires/{$b->id}", ['name' => 'Hotel Test', 'category' => 'hotel', 'city' => 'Brazzaville', 'is_active' => true, 'publish_preview' => true])->assertRedirect();
        $b->refresh();
        $this->assertSame('https://example.com/hotel.jpg', $b->image);
        $this->assertNull($b->preview_image);
    }
}
