# Module Fret & Logistique (marketplace de partenaires verifies)

Conforme au cahier de cadrage de novembre 2026 (phases 1 et 2 : marketplace, devis, commandes, paiement, suivi, commissions).

## Flux
1. **Partenaire** : candidature (`/partner/fret/profil`) + pieces (disque prive) -> statut `pending`. Le staff approuve / refuse / demande une correction / suspend (motif obligatoire, historique dans `freight_audit_logs`).
2. **Offres** : le partenaire cree une offre (aerien/maritime/routier, tarif instantane ou sur devis, validite, inclusions/exclusions) -> moderation existante (`pending` -> `published`). Une modification sensible (tarif, destination, conditions) d'une offre publiee la renvoie en validation. Une offre n'est visible que si son partenaire est approuve et si elle n'a pas expire.
3. **Client** : tarif instantane (prix recalcule cote serveur) ou demande de devis (champs adaptes au mode) -> devis detaille et versionne -> **acceptation explicite** -> commande avec conditions figees.
4. **Paiement** : via `PaymentService` (Mobile Money), verifie cote serveur ; `FreightPaymentBridge` met a jour la commande. Doublons / paiements tardifs : journalises (`payment.unexpected`), jamais appliques silencieusement.
5. **Statuts distincts** : `DEVIS_ACCEPTE` -> `PAIEMENT_EN_ATTENTE` -> `PAIEMENT_CONFIRME` -> `RESERVATION_CONFIRMEE` (par le partenaire) ; `ANNULE`, `LITIGE`. Le suivi de transport ne demarre qu'apres la confirmation de reservation.
6. **Suivi** : evenements ajout-seul (statut, lieu, date, source, commentaire, preuve) ; une erreur se corrige par un nouvel evenement.
7. **Finance** : commission = taux par defaut (`FREIGHT_COMMISSION_PERCENT`, a confirmer par contrat) ou taux du partenaire ; remboursement et reversement suivis separement.
8. **Reclamations** : ouverture apres paiement, echanges, decision documentee par le staff.

## API mobile (auth Sanctum sauf mention)
| Route | Usage |
|---|---|
| GET `/api/fret/offers`, `/api/fret/offers/{slug}` (public) | Catalogue |
| POST `/api/fret/offers/{slug}/book` | Tarif instantane -> reservation de paiement (`id`) |
| GET/POST `/api/fret/quote-requests`, GET `/api/fret/quote-requests/{ref}` | Demandes de devis |
| POST `/api/fret/quotes/{ref}/accept` (`accept_terms: true`) / `/refuse` | Acceptation / refus |
| GET `/api/fret/orders`, `/orders/{ref}`, POST `/orders/{ref}/cancel` | Commandes (paiement : `POST /api/bookings/{booking_id}/pay`) |
| GET `/api/fret/shipments/{code}/tracking` (public) | Suivi |
| GET/POST `/api/fret/claims`, POST `/api/fret/claims/{id}/messages` | Reclamations |
| POST `/api/fret/documents` | Pieces (facture commerciale, colisage...) |
| GET `/api/fret/notifications` | Notifications |

## Limites connues / a faire
- Roles partenaire distincts (proprietaire, gestionnaire, commercial, operations, comptabilite), clients entreprise (organisations) et superviseur : non implementes (un compte = un partenaire).
- MFA des roles sensibles, notifications SMS/push : non implementes (application + e-mail).
- Le disque prive (`storage/app/private`) est ephemere sur Railway sans volume persistant : brancher un volume ou un stockage objet avant la mise en production.
- Remboursement : enregistrement manuel par le staff (pas d'API de remboursement PSP).
- Phase 3 (API transporteurs, suivi automatique) : non couverte.
