<?php

namespace App\Services\Flights\Contracts;

use App\Models\Flights\FlightBooking;
use App\Models\Flights\FlightOffer;
use App\Models\Flights\FlightSupplier as SupplierModel;
use App\Services\Flights\Data\OfferData;
use App\Services\Flights\Data\SearchCriteria;
use App\Services\Flights\Data\SupplierResult;
use Illuminate\Http\Request;

/**
 * Connecteur fournisseur aerien (consolidateur, GDS, agregateur NDC).
 * Architecture modulaire : un fournisseur = une classe, enregistree dans
 * config/flights.php -> drivers.
 */
interface FlightSupplier
{
    public function setSupplier(SupplierModel $supplier): static;

    /** @return OfferData[] */
    public function search(SearchCriteria $criteria): array;

    /** Revalide le prix et la disponibilite. null = offre plus disponible. */
    public function price(FlightOffer $offer, SearchCriteria $criteria): ?OfferData;

    /** Cree la reservation (PNR). La cle d'idempotence doit etre transmise au fournisseur s'il la supporte. */
    public function book(FlightBooking $booking, string $idempotencyKey): SupplierResult;

    /** Demande l'emission des billets. */
    public function issue(FlightBooking $booking, string $idempotencyKey): SupplierResult;

    /** Etat reel de la reservation et des billets chez le fournisseur. */
    public function retrieve(FlightBooking $booking): SupplierResult;

    public function cancel(FlightBooking $booking): SupplierResult;

    /** Verifie la signature d'un webhook ; renvoie [reference, SupplierResult] ou null. */
    public function parseWebhook(Request $request): ?array;
}
