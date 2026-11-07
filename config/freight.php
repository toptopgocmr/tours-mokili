<?php

return [
    // Commission par defaut (%) sur chaque commande payee. A CONFIRMER par contrat ; surchargeable par partenaire.
    'commission_percent' => (float) env('FREIGHT_COMMISSION_PERCENT', 8),

    // Types de pieces de candidature attendues.
    'partner_document_types' => ['registre_commerce', 'licence_transport', 'assurance', 'identite_dirigeant', 'autre'],

    // Duree de validite par defaut d'un devis (jours).
    'quote_validity_days' => (int) env('FREIGHT_QUOTE_VALIDITY_DAYS', 7),

    // Poids/volume : coefficient volumetrique aerien (kg par m3).
    'air_volumetric_kg_per_m3' => 167,
];
