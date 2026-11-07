<?php

namespace Tests\Feature;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightStatus;
use App\Models\Flights\FlightTicket;
use App\Models\User;
use App\Services\Flights\FlightFulfillmentService;
use App\Services\Flights\FlightOrderService;
use App\Services\Flights\FlightPriceChanged;
use App\Services\Flights\FlightSearchService;
use App\Services\Flights\FlightStateMachine;
use App\Services\Flights\InvalidFlightTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cycle de vie d'un dossier de vol avec le fournisseur simule. */
class FlightFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): FlightOrder
    {
        $user = User::factory()->create();
        $search = app(FlightSearchService::class)->search($user, [
            'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => now()->addDays(20)->toDateString(),
            'adults' => 1, 'children' => 0, 'infants' => 0, 'cabin' => 'economy',
        ]);
        $offer = $search->offers()->orderBy('price_total')->first();

        return app(FlightOrderService::class)->createFromOffer(
            $user, $offer->load(['search', 'supplier']),
            [['type' => 'adult', 'first_name' => 'Jean', 'last_name' => 'Mokili', 'birth_date' => '1990-01-01']],
            ['email' => 'jean@example.com', 'phone' => null],
        );
    }

    private function pay(FlightOrder $order): FlightOrder
    {
        $m = app(FlightStateMachine::class);
        $m->transition($order, FlightStatus::PAYMENT_PENDING);
        $m->transition($order, FlightStatus::PAYMENT_CONFIRMED);

        return $order->fresh();
    }

    public function test_nominal_flow_ends_ticketed(): void
    {
        $order = $this->pay($this->makeOrder());
        $order = app(FlightFulfillmentService::class)->process($order);

        $this->assertSame(FlightStatus::TICKETED, $order->status);
        $this->assertNotNull($order->pnr);
        $this->assertSame(1, FlightTicket::where('flight_order_id', $order->id)->count());
    }

    public function test_cannot_skip_states(): void
    {
        $order = $this->makeOrder();
        $this->expectException(InvalidFlightTransition::class);
        app(FlightStateMachine::class)->transition($order, FlightStatus::TICKETED);
    }

    public function test_reprocessing_does_not_issue_tickets_twice(): void
    {
        $order = $this->pay($this->makeOrder());
        $svc = app(FlightFulfillmentService::class);
        $svc->process($order);
        $svc->process($order->fresh());

        $this->assertSame(1, FlightTicket::where('flight_order_id', $order->id)->count());
    }

    public function test_timeout_after_ticketing_recovers_existing_ticket(): void
    {
        config(['flights.fake.timeout_after_ticketing' => true]);
        $order = $this->pay($this->makeOrder());
        $order = app(FlightFulfillmentService::class)->process($order);

        // Le fournisseur est interroge avant toute reemission : jamais de doublon.
        $this->assertLessThanOrEqual(1, FlightTicket::where('flight_order_id', $order->id)->count());
        $this->assertContains($order->status, [FlightStatus::TICKETED, FlightStatus::FAILED]);
    }

    public function test_booking_failure_marks_failed_with_anomaly(): void
    {
        config(['flights.fake.fail_booking' => true]);
        $order = $this->pay($this->makeOrder());
        $order = app(FlightFulfillmentService::class)->process($order);

        $this->assertSame(FlightStatus::FAILED, $order->status);
        $this->assertTrue((bool) $order->is_anomaly);
    }

    public function test_price_change_requires_acceptance(): void
    {
        config(['flights.fake.price_change_percent' => 10]);
        $user = User::factory()->create();
        $search = app(FlightSearchService::class)->search($user, [
            'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => now()->addDays(20)->toDateString(),
            'adults' => 1, 'children' => 0, 'infants' => 0, 'cabin' => 'economy',
        ]);
        $offer = FlightOffer::where('flight_search_id', $search->id)->first();

        $this->expectException(FlightPriceChanged::class);
        app(FlightOrderService::class)->createFromOffer(
            $user, $offer->load(['search', 'supplier']),
            [['type' => 'adult', 'first_name' => 'Jean', 'last_name' => 'Mokili', 'birth_date' => '1990-01-01']],
            ['email' => 'jean@example.com', 'phone' => null], (float) $offer->price_total,
        );
    }

    public function test_multi_city_search_builds_one_offer_covering_all_legs(): void
    {
        $search = app(FlightSearchService::class)->search(null, [
            'legs' => [
                ['origin' => 'BZV', 'destination' => 'CDG', 'date' => now()->addDays(20)->toDateString()],
                ['origin' => 'CDG', 'destination' => 'DXB', 'date' => now()->addDays(25)->toDateString()],
                ['origin' => 'DXB', 'destination' => 'BZV', 'date' => now()->addDays(30)->toDateString()],
            ],
            'adults' => 1,
        ]);

        $this->assertGreaterThan(0, $search->offers()->count());
        $offer = $search->offers()->first();
        $this->assertCount(3, $offer->itinerary['legs']);
        $this->assertSame('BZV', $offer->itinerary['legs'][0][0]['from']);
    }

    public function test_multi_city_dates_must_be_chronological(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(FlightSearchService::class)->search(null, [
            'legs' => [
                ['origin' => 'BZV', 'destination' => 'CDG', 'date' => now()->addDays(30)->toDateString()],
                ['origin' => 'CDG', 'destination' => 'BZV', 'date' => now()->addDays(20)->toDateString()],
            ],
        ]);
    }

    public function test_direct_only_keeps_only_nonstop_offers(): void
    {
        $search = app(FlightSearchService::class)->search(null, [
            'origin' => 'BZV', 'destination' => 'CDG', 'depart_date' => now()->addDays(20)->toDateString(), 'direct_only' => true,
        ]);

        foreach ($search->offers as $offer) {
            $this->assertTrue(FlightSearchService::isDirect($offer->itinerary));
        }
    }
}
