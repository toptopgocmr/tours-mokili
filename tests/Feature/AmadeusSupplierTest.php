<?php

namespace Tests\Feature;

use App\Models\Flights\FlightSearch;
use App\Services\Flights\Suppliers\AmadeusFlightSupplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmadeusSupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_maps_amadeus_offers(): void
    {
        config(['flights.amadeus.client_id' => 'id', 'flights.amadeus.client_secret' => 'secret']);
        Cache::flush();

        Http::fake([
            '*/security/oauth2/token' => Http::response(['access_token' => 'tok', 'expires_in' => 1799]),
            '*/shopping/flight-offers' => Http::response([
                'data' => [[
                    'id' => '1', 'validatingAirlineCodes' => ['AF'],
                    'itineraries' => [['segments' => [[
                        'departure' => ['iataCode' => 'BZV', 'at' => '2026-12-01T10:00:00'],
                        'arrival' => ['iataCode' => 'CDG', 'at' => '2026-12-01T18:00:00'],
                        'carrierCode' => 'AF', 'number' => '123', 'duration' => 'PT8H', 'numberOfStops' => 0,
                    ]]]],
                    'price' => ['grandTotal' => '425000', 'currency' => 'XAF'],
                    'travelerPricings' => [['fareDetailsBySegment' => [['includedCheckedBags' => ['quantity' => 1]]]]],
                ]],
                'dictionaries' => ['carriers' => ['AF' => 'AIR FRANCE']],
            ]),
        ]);

        $search = new FlightSearch([
            'origin' => 'BZV', 'destination' => 'CDG', 'adults' => 1, 'children' => 0, 'infants' => 0, 'cabin' => 'economy',
        ]);
        $search->depart_date = now()->addDays(30);

        $offers = app(AmadeusFlightSupplier::class)->search($search);

        $this->assertCount(1, $offers);
        $this->assertSame('AF123', $offers[0]['itinerary']['outbound'][0]['flight_number']);
        $this->assertSame(480, $offers[0]['itinerary']['outbound'][0]['duration_minutes']);
        $this->assertSame(425000.0, $offers[0]['cost']);
        $this->assertSame('AIR FRANCE', $offers[0]['airline_name']);
    }
}
