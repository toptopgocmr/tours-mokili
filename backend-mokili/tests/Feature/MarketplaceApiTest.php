<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Marketplace depuis l'app : options, deposer, mes annonces, modifier, vendu, supprimer. */
class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return $over + [
            'listing_type' => 'vente', 'title' => 'Samsung A54 neuf', 'category' => 'telephones',
            'description' => 'Neuf sous emballage, garantie 1 an.', 'price' => 180000, 'negotiable' => true,
            'condition' => 'neuf', 'city' => 'Pointe-Noire', 'contact_phone' => '+242 06 000 00 00',
        ];
    }

    public function test_app_seller_flow(): void
    {
        Storage::fake('public');
        $this->getJson('/api/marketplace/options')->assertOk()->assertJsonPath('categories.immobilier', 'Immobilier')->assertJsonPath('max_photos', 6);
        $this->postJson('/api/marketplace/annonces', $this->payload())->assertUnauthorized();

        $seller = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($seller);

        $this->postJson('/api/marketplace/annonces', $this->payload(['title' => '']))->assertUnprocessable()->assertJsonValidationErrors('title');

        $res = $this->post('/api/marketplace/annonces', $this->payload() + ['photos' => [UploadedFile::fake()->image('a.jpg')]], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('product.status', 'pending');
        $product = Product::findOrFail($res->json('product.id'));
        $this->assertCount(1, $product->gallery);

        $this->getJson('/api/marketplace/mine')->assertOk()->assertJsonPath('data.0.title', 'Samsung A54 neuf');

        $this->post("/api/marketplace/annonces/{$product->id}", $this->payload(['price' => 170000]) + [
            'remove_photos' => [$product->gallery[0]], 'photos' => [UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
        $product->refresh();
        $this->assertEquals(170000, $product->price);
        $this->assertCount(2, $product->gallery);

        $this->postJson("/api/marketplace/annonces/{$product->id}/vendu")->assertOk();
        $this->assertNotNull($product->fresh()->sold_at);

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/marketplace/annonces/{$product->id}")->assertForbidden();

        Sanctum::actingAs($seller);
        $this->deleteJson("/api/marketplace/annonces/{$product->id}")->assertOk();
        $this->assertNull(Product::find($product->id));
    }

    public function test_public_filters_and_sort(): void
    {
        $this->getJson('/api/marketplace/products?sort=prix_asc&condition=neuf&listing_type=vente&price_max=100000')->assertOk();
    }
}
