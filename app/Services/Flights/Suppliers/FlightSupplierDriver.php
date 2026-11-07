<?php

namespace App\Services\Flights\Suppliers;

use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightOrder;
use App\Models\Flights\FlightSearch;

/**
 * Contrat d'un connecteur fournisseur aerien / consolidateur.
 *
 * Regles :
 *  - createBooking() et issueTickets() recoivent une cle d'idempotence ;
 *    le fournisseur (ou le connecteur) ne doit jamais creer deux fois la
 *    meme operation pour la meme cle.
 *  - Sur timeout, lever SupplierTimeoutException (resultat INCONNU) ; le
 *    moteur appellera ensuite findBooking()/findTickets() AVANT toute relance.
 *  - Un refus definitif leve SupplierRejectedException.
 */
interface FlightSupplierDriver
{
    /**
     * @return array<int, array{external_id:string, itinerary:array, airline_code:?string, airline_name:?string, cost:float, currency:string, fare_rules:array, expires_at:\DateTimeInterface}>
     */
    public function search(FlightSearch $search): array;

    /** @return array{available:bool, cost:float, currency:string} */
    public function revalidate(FlightOffer $offer): array;

    /** @return array{pnr:string, supplier_order_id:string} */
    public function createBooking(FlightOrder $order, string $idempotencyKey): array;

    /** Etat reel d'une reservation deja demandee, ou null si le fournisseur ne la connait pas. */
    public function findBooking(string $idempotencyKey): ?array;

    /** @return array<int, string> numeros de billet indexes par flight_passenger_id */
    public function issueTickets(FlightOrder $order, string $idempotencyKey): array;

    /** @return array<int, string>|null billets deja emis pour cette cle, ou null */
    public function findTickets(string $idempotencyKey): ?array;

    public function cancelBooking(FlightOrder $order): bool;
}
