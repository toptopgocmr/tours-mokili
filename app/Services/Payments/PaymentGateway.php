<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;

/**
 * Contrat commun aux 4 agregateurs (Peex, pawaPay, MaishaPay, Digitwace).
 * Les appels sont asynchrones cote Mobile Money : collect()/payout()
 * renvoient le plus souvent PENDING, le statut final arrive par webhook
 * (handleWebhook) ou par interrogation (status).
 */
interface PaymentGateway
{
    /** Identifiant technique : peex, pawapay, maishapay, digitwace. */
    public function name(): string;

    /** Les cles API necessaires sont-elles presentes dans l'environnement ? */
    public function isConfigured(): bool;

    /** PAY-IN : demande de paiement envoyee sur le telephone du client. */
    public function collect(PaymentRequest $request): GatewayResult;

    /** PAY-OUT : envoi d'argent vers un portefeuille Mobile Money. */
    public function payout(PaymentRequest $request): GatewayResult;

    /** Statut d'une operation ($type = collect | payout). */
    public function status(string $reference, ?string $providerReference, string $type = 'collect'): GatewayResult;

    /**
     * Lit un callback de l'agregateur. Renvoie null si la requete n'est pas
     * authentique ou pas exploitable. ourReference = notre reference.
     */
    public function handleWebhook(Request $request): ?GatewayResult;
}
