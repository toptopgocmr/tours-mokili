<?php

namespace Tests\Feature;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ecran de paiement : tous les pays, payables en premier, XOF pour l'Afrique de l'Ouest. */
class CheckoutCountriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_lists_every_country_with_payable_first(): void
    {
        config(['payments.gateways.pawapay.token' => 'test-token']);
        $m = app(PaymentGatewayManager::class);

        $all = collect($m->catalogue('collect', true)['countries']);
        $this->assertSame(count(config('payments.country_names')), $all->count());
        $this->assertNotEmpty($all->first()['gateways']);
        $this->assertTrue($all->firstWhere('code', 'SN')['gateways'] === ['pawapay']);
        $this->assertSame([], $all->firstWhere('code', 'KE')['gateways']);

        // L'API mobile ne garde que les pays payables.
        $api = collect($m->catalogue('collect')['countries']);
        $this->assertNull($api->firstWhere('code', 'KE'));
    }
}
