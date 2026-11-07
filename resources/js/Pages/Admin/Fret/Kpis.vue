<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ kpis: Object });
const k = props.kpis;
const fmt = (n) => Number(n ?? 0).toLocaleString('fr-FR');
const cards = [
    ['Partenaires verifies actifs', k.active_partners], ['Candidatures a traiter', k.pending_partners],
    ['Offres aerien / maritime / routier', `${k.offers_by_mode.air ?? 0} / ${k.offers_by_mode.mer ?? 0} / ${k.offers_by_mode.route ?? 0}`],
    ['Demandes de devis', k.quote_requests], ['Delai moyen de reponse (min)', k.avg_response_minutes ?? '-'],
    ['Taux de devis acceptes', k.quote_accept_rate === null ? '-' : k.quote_accept_rate + ' %'], ['Reservations confirmees', k.confirmed_orders],
    ['Prises en charge', k.picked_up], ['Livrees', k.delivered], ['Reclamations (ouvertes)', `${k.claims} (${k.open_claims})`],
    ['Volume d\'affaires', fmt(k.turnover)], ['Commissions', fmt(k.commissions)], ['Remboursements', fmt(k.refunds)],
];
</script>

<template>
    <Head title="Indicateurs fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Indicateurs Fret & Logistique</h1>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="c in cards" :key="c[0]" class="console-panel"><p class="text-xs text-slate-500">{{ c[0] }}</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ c[1] }}</p></div>
    </div>
</template>
