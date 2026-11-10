<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Logement\LodgingListing;
use App\Models\Payment;
use App\Models\User;
use App\Support\PaymentMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Recu de paiement + messages sans nom d'agregateur. */
class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_booking_has_a_receipt_only_for_its_owner(): void
    {
        $client = User::factory()->create(['name' => 'Jean Mabiala']);
        $l = LodgingListing::create(['title' => 'Suite Fleuve', 'city' => 'Brazzaville', 'country' => 'CG', 'price_per_night' => 35000, 'status' => 'published', 'is_active' => true]);
        $b = Booking::create(['user_id' => $client->id, 'bookable_type' => LodgingListing::class, 'bookable_id' => $l->id, 'quantity' => 2, 'unit_price' => 35000, 'total_amount' => 70000, 'currency' => 'XAF', 'status' => 'awaiting_payment', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(3)]);

        $this->actingAs($client)->get("/checkout/{$b->id}/recu")->assertNotFound(); // pas encore paye

        Payment::create(['booking_id' => $b->id, 'user_id' => $client->id, 'provider' => 'maishapay', 'method' => 'mobile_money', 'phone' => '242067621919', 'country_code' => 'CG', 'network' => 'MTN', 'amount' => 70000, 'currency' => 'XAF', 'status' => 'paid', 'paid_at' => now(), 'gateway_reference' => 'TX-123']);
        $b->update(['status' => 'confirmed']);

        $this->get("/checkout/{$b->id}/recu")->assertOk()
            ->assertSee('RECU DE PAIEMENT')->assertSee('Suite Fleuve')->assertSee('Jean Mabiala')->assertSee('70 000')
            ->assertSee('MTN Mobile Money')->assertDontSee('maishapay')->assertDontSee('067621919');
        $this->get("/checkout/{$b->id}")->assertInertia(fn ($p) => $p->where('receiptUrl', route('checkout.receipt', $b)));

        $this->actingAs(User::factory()->create())->get("/checkout/{$b->id}/recu")->assertForbidden();
    }

    public function test_gateway_names_are_removed_from_client_messages(): void
    {
        $this->assertSame('La demande de paiement a echoue.', PaymentMessage::clean('La demande de paiement MaishaPay a echoue.'));
        $this->assertSame('Solde insuffisant', PaymentMessage::clean('pawaPay Solde insuffisant'));
    }
}
