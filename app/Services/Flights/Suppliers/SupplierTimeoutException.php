<?php

namespace App\Services\Flights\Suppliers;

/** Le resultat de l'operation est INCONNU : interroger le fournisseur avant de relancer. */
class SupplierTimeoutException extends SupplierException {}
