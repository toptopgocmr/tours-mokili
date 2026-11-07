<?php

namespace Tests\Feature;

use App\Models\Fret\FreightOrder;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\Fret\Quote;
use App\Models\Payment;
use App\Models\User;
use App\Services\Fret\FreightOrderService;
use App\Services\Fret\FreightQuoteService;
use App\Services\Fret\FreightTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Cycle devis -> commande -> paiement -> reservation -> suivi (cahier de cadrage Fret & Logistique). */
class FreightMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function partner(string $name = 'Partner'): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->forceFill(['role' => 'partner'])->save();
        FreightPartnerProfile::create(['user_id' => $u->id, 'company_name' => $name, 'status' => 'approved', 'modes' => ['air']]);

        return $u;
    }

    private function sentQuote(User $client, User $partner, ?string $validUntil = null): Quote
    {
        $svc = app(FreightQuoteService::class);
        $request = $svc->createRequest($client, [
            'mode' => 'air', 'origin_country' => 'CG', 'origin_city' => 'Brazzaville', 'destination_country' => 'FR',
            'destination_city' => 'Paris', 'cargo_type' => 'colis', 'description' => 'Cartons de vetements', 'weight_kg' => 20,
            'dimensions' => '60x40x40',
        ]);

        return $svc->respond($partner, $request, [
            'currency' => 'XAF',
            'lines' => [['label' => 'Transport', 'amount' => 100000, 'kind' => 'included'], ['label' => 'Douane', 'amount' => 5000, 'kind' => 'excluded']],
            'valid_until' => $validUntil ?? now()->addDays(5)->toDateString(),
        ]);
    }

    private function payment(): Payment
    {
        return (new Payment)->forceFill(['id' => 999, 'provider' => 'test', 'amount' => 100000]);
    }

    public function test_accepting_a_quote_freezes_terms_and_computes_commission(): void
    {
        config(['freight.commission_percent' => 8]);
        $client = User::factory()->create();
        $quote = $this->sentQuote($client, $this->partner());

        $order = app(FreightQuoteService::class)->accept($client, $quote, true);

        $this->assertSame(FreightOrder::ACCEPTED, $order->status);
        $this->assertEquals(100000.0, (float) $order->total_amount); // la ligne "non inclus" n'entre pas dans le total
        $this->assertEquals(8000.0, (float) $order->commission_amount);
        $this->assertEquals(92000.0, (float) $order->partner_amount);
        $this->assertSame($quote->reference, $order->frozen_terms['quote_reference']);
        $this->assertNotNull($order->shipment->tracking_code);
    }

    public function test_quote_requires_explicit_acceptance_and_cannot_be_accepted_twice(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client, $this->partner());
        $svc = app(FreightQuoteService::class);

        try {
            $svc->accept($client, $quote, false);
            $this->fail('Acceptation sans consentement explicite acceptee.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('explicitement', $e->getMessage());
        }

        $svc->accept($client, $quote->fresh(), true);
        $this->expectException(\RuntimeException::class);
        $svc->accept($client, $quote->fresh(), true);
    }

    public function test_expired_quote_is_rejected(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client, $this->partner());
        $quote->update(['valid_until' => now()->subDay()->toDateString()]);

        $this->expectException(\RuntimeException::class);
        app(FreightQuoteService::class)->accept($client, $quote->fresh(), true);
    }

    public function test_payment_does_not_confirm_reservation_and_tracking_waits_for_confirmation(): void
    {
        $client = User::factory()->create();
        $partner = $this->partner();
        $order = app(FreightQuoteService::class)->accept($client, $this->sentQuote($client, $partner), true);
        $orders = app(FreightOrderService::class);

        $orders->paymentInitiated($order);
        $orders->paymentSettled($order->fresh(), $this->payment(), true);
        $order = $order->fresh();

        // Paiement confirme <> reservation confirmee.
        $this->assertSame(FreightOrder::PAID, $order->status);
        $this->assertNotNull($order->payment_confirmed_at);
        $this->assertNull($order->confirmed_at);

        // Pas de suivi tant que le partenaire n'a pas confirme.
        try {
            app(FreightTrackingService::class)->add($order->shipment, 'PRISE_EN_CHARGE', 'Brazzaville', null, 'partner:'.$partner->id);
            $this->fail('Suivi accepte avant la confirmation de reservation.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('confirmation', $e->getMessage());
        }

        $orders->confirmReservation($partner, $order, 'Deposer le colis a 8h, entrepot 2.');
        $event = app(FreightTrackingService::class)->add($order->fresh()->shipment, 'PRISE_EN_CHARGE', 'Brazzaville', null, 'partner:'.$partner->id);

        $this->assertSame('prise_en_charge', $order->fresh()->shipment->status);
        $this->assertNotNull($event->id);
    }

    public function test_duplicate_payment_is_logged_not_applied(): void
    {
        $client = User::factory()->create();
        $order = app(FreightQuoteService::class)->accept($client, $this->sentQuote($client, $this->partner()), true);
        $orders = app(FreightOrderService::class);

        $orders->paymentSettled($order->fresh(), $this->payment(), true);
        $orders->paymentSettled($order->fresh(), $this->payment(), true); // doublon (webhook rejoue)

        $this->assertSame(FreightOrder::PAID, $order->fresh()->status);
        $this->assertDatabaseHas('freight_audit_logs', ['action' => 'payment.unexpected']);
    }

    public function test_another_partner_cannot_confirm_an_order(): void
    {
        $client = User::factory()->create();
        $owner = $this->partner('Owner');
        $other = $this->partner('Other');
        $order = app(FreightQuoteService::class)->accept($client, $this->sentQuote($client, $owner), true);
        app(FreightOrderService::class)->paymentSettled($order->fresh(), $this->payment(), true);

        $this->expectException(HttpException::class);
        app(FreightOrderService::class)->confirmReservation($other, $order->fresh(), 'Instructions non autorisees');
    }

    public function test_cancelled_paid_order_is_refunded_separately(): void
    {
        $client = User::factory()->create();
        $admin = User::factory()->create();
        $order = app(FreightQuoteService::class)->accept($client, $this->sentQuote($client, $this->partner()), true);
        $orders = app(FreightOrderService::class);

        $orders->paymentSettled($order->fresh(), $this->payment(), true);
        $order = $orders->cancel($order->fresh(), 'admin:'.$admin->id, 'Marchandise refusee');
        $this->assertNull($order->refunded_at); // l'annulation n'est pas le remboursement

        $order = $orders->markRefunded($order, $admin, 'Virement manuel');
        $this->assertNotNull($order->refunded_at);
    }
}
