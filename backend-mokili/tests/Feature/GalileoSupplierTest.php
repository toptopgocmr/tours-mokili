<?php

namespace Tests\Feature;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSearch;
use App\Models\Flights\FlightSupplier;
use App\Models\User;
use App\Services\Flights\FlightBookingService;
use App\Services\Flights\FlightStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GalileoSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flights.galileo' => array_merge(config('flights.galileo'), [
            'username' => 'Universal API/uAPI-test', 'password' => 'secret', 'target_branch' => 'P7000000',
            'endpoint' => 'https://galileo.test/AirService',
        ])]);
        FlightSupplier::where('code', 'manuel')->update(['is_active' => false]);
    }

    protected function lowFareResponse(): string
    {
        $seg = fn ($k, $g, $c, $n, $o, $d, $dep, $arr, $ft) => "<air:AirSegment Key=\"$k\" Group=\"$g\" Carrier=\"$c\" FlightNumber=\"$n\" Origin=\"$o\" Destination=\"$d\" DepartureTime=\"$dep\" ArrivalTime=\"$arr\" FlightTime=\"$ft\" Equipment=\"788\"/>";

        return '<SOAP:Envelope xmlns:SOAP="http://schemas.xmlsoap.org/soap/envelope/"><SOAP:Body>'
            .'<air:LowFareSearchRsp xmlns:air="http://www.travelport.com/schema/air_v52_0" xmlns:common_v52_0="http://www.travelport.com/schema/common_v52_0">'
            .'<air:FlightDetailsList/><air:AirSegmentList>'
            .$seg('S1', 0, 'ET', '861', 'BZV', 'ADD', '2026-12-14T12:10:00.000+01:00', '2026-12-14T20:40:00.000+03:00', 330)
            .$seg('S2', 0, 'ET', '600', 'ADD', 'DXB', '2026-12-15T01:30:00.000+03:00', '2026-12-15T06:30:00.000+04:00', 240)
            .$seg('S3', 1, 'ET', '601', 'DXB', 'ADD', '2026-12-21T04:25:00.000+04:00', '2026-12-21T07:30:00.000+03:00', 245)
            .$seg('S4', 1, 'ET', '862', 'ADD', 'BZV', '2026-12-21T09:00:00.000+03:00', '2026-12-21T11:10:00.000+01:00', 370)
            .'</air:AirSegmentList><air:FareInfoList><air:FareInfo Key="F1" FareBasis="TLOWCG"><air:BaggageAllowance><air:NumberOfPieces>2</air:NumberOfPieces></air:BaggageAllowance></air:FareInfo></air:FareInfoList>'
            .'<air:AirPricingSolution Key="SOL1" TotalPrice="XAF767094">'
            .'<air:Journey TravelTime="P0DT11H45M0S"><air:AirSegmentRef Key="S1"/><air:AirSegmentRef Key="S2"/></air:Journey>'
            .'<air:Journey><air:AirSegmentRef Key="S3"/><air:AirSegmentRef Key="S4"/></air:Journey>'
            .'<air:AirPricingInfo Key="P1" TotalPrice="XAF767094" PlatingCarrier="ET" Refundable="true">'
            .'<air:FareInfoRef Key="F1"/><air:BookingInfo BookingCode="T" SegmentRef="S1"/><air:BookingInfo BookingCode="T" SegmentRef="S2"/>'
            .'<air:BookingInfo BookingCode="L" SegmentRef="S3"/><air:BookingInfo BookingCode="L" SegmentRef="S4"/>'
            .'<air:PassengerType Code="ADT"/><air:ChangePenalty><air:Amount>XAF65000</air:Amount></air:ChangePenalty></air:AirPricingInfo>'
            .'</air:AirPricingSolution></air:LowFareSearchRsp></SOAP:Body></SOAP:Envelope>';
    }

    protected function search(): FlightSearch
    {
        $this->get('/voyage/vols/recherche?'.http_build_query([
            'trip_type' => 'roundtrip', 'origin' => 'BZV', 'destination' => 'DXB',
            'depart_date' => '2026-12-14', 'return_date' => '2026-12-21', 'adults' => 1, 'cabin' => 'economy',
        ]))->assertRedirect();

        return FlightSearch::latest('id')->firstOrFail();
    }

    public function test_galileo_is_ignored_without_credentials(): void
    {
        config(['flights.galileo.username' => null]);
        Http::fake();
        $this->travelTo('2026-11-10');
        $search = $this->search();
        $this->assertSame(0, $search->results_count);
        Http::assertNothingSent();
    }

    public function test_low_fare_search_maps_solutions_with_real_connections(): void
    {
        $this->travelTo('2026-11-10');
        Http::fake(['galileo.test/*' => Http::response($this->lowFareResponse(), 200)]);

        $search = $this->search();
        $this->assertSame(1, $search->results_count);
        $offer = $search->offers()->firstOrFail();

        $this->assertEquals(767094, (float) $offer->total_amount);
        $this->assertSame('ET', $offer->validating_airline);
        $this->assertSame('Ethiopian Airlines', $offer->airline_name);
        $this->assertCount(2, $offer->slices);
        $out = $offer->slices[0];
        $this->assertSame(['BZV', 'DXB', 1, ['ADD']], [$out['origin'], $out['destination'], $out['stops'], $out['stop_airports']]);
        $this->assertSame(15 * 60 + 20, $out['duration_minutes']); // 11:10 UTC -> 02:30 UTC le lendemain
        $this->assertCount(2, $out['segments']);
        $this->assertSame('2 bagages en soute', $offer->fare_rules['baggage']);
        $this->assertEquals(65000, $offer->fare_rules['change_fee']);
        $this->assertSame('T', $offer->raw['galileo']['segments'][0]['class']);

        Http::assertSent(fn ($r) => str_contains($r->body(), 'LowFareSearchReq') && str_contains($r->body(), 'TargetBranch="P7000000"')
            && str_contains($r->body(), 'Code="DXB"') && $r->hasHeader('Authorization'));
    }

    public function test_price_revalidation_uses_air_price_and_detects_changes(): void
    {
        $this->travelTo('2026-11-10');
        Http::fakeSequence('galileo.test/*')
            ->push($this->lowFareResponse())
            ->push('<SOAP:Envelope xmlns:SOAP="http://schemas.xmlsoap.org/soap/envelope/"><SOAP:Body><air:AirPriceRsp xmlns:air="http://www.travelport.com/schema/air_v52_0"><air:AirPriceResult>'
                .'<air:AirPricingSolution Key="X" TotalPrice="XAF790000"><air:AirPricingInfo TotalPrice="XAF790000" PlatingCarrier="ET" Refundable="true"><air:PassengerType Code="ADT"/></air:AirPricingInfo></air:AirPricingSolution>'
                .'</air:AirPriceResult></air:AirPriceRsp></SOAP:Body></SOAP:Envelope>');

        $offer = $this->search()->offers()->firstOrFail();
        $changed = app(FlightBookingService::class)->revalidateOffer($offer);

        $this->assertTrue($changed);
        $this->assertEquals(790000, (float) $offer->fresh()->total_amount);
        $this->assertCount(2, $offer->fresh()->slices[0]['segments']); // memes troncons
        Http::assertSent(fn ($r) => str_contains($r->body(), 'AirPriceReq') && str_contains($r->body(), 'ClassOfService="T"'));
    }

    public function test_no_availability_fault_returns_no_offer(): void
    {
        $this->travelTo('2026-11-10');
        Http::fake(['galileo.test/*' => Http::response('<SOAP:Envelope xmlns:SOAP="http://schemas.xmlsoap.org/soap/envelope/"><SOAP:Body><SOAP:Fault><faultcode>Server.Business</faultcode><faultstring>NO AVAILABILITY FOR THIS REQUEST</faultstring></SOAP:Fault></SOAP:Body></SOAP:Envelope>', 500)]);

        $search = $this->search();
        $this->assertSame(0, $search->results_count);
        $this->assertNull($search->supplier_errors);
    }
}
