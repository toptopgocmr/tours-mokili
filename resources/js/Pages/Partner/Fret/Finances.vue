<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ totals: Object, stats: Object, orders: Array });
const fmt = (n) => Number(n).toLocaleString('fr-FR');
</script>

<template>
    <Head title="Finances fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Finances et statistiques</h1>
    <div class="mb-4 grid gap-3 sm:grid-cols-5">
        <div v-for="(v, k) in { Facture: totals.billed, Commission: totals.commission, 'Votre part': totals.partner, Reverse: totals.settled, Rembourse: totals.refunded }" :key="k" class="console-panel text-center"><p class="text-xs text-slate-500">{{ k }}</p><p class="text-lg font-bold">{{ fmt(v) }}</p></div>
    </div>
    <div class="mb-4 grid gap-3 sm:grid-cols-5">
        <div v-for="(v, k) in { Demandes: stats.requests, 'Devis envoyes': stats.quotes_sent, 'Devis acceptes': stats.quotes_accepted, Commandes: stats.orders, Livrees: stats.delivered }" :key="k" class="console-panel text-center"><p class="text-xs text-slate-500">{{ k }}</p><p class="text-lg font-bold">{{ v }}</p></div>
    </div>
    <div class="console-panel overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th>Commande</th><th>Statut</th><th class="text-right">Total</th><th class="text-right">Commission</th><th class="text-right">Votre part</th><th>Reglement</th></tr></thead>
        <tbody><tr v-for="o in orders" :key="o.id" class="border-t"><td class="py-1">{{ o.reference }}</td><td>{{ o.status }}</td><td class="text-right">{{ fmt(o.total_amount) }}</td><td class="text-right">{{ fmt(o.commission_amount) }}</td><td class="text-right">{{ fmt(o.partner_amount) }}</td><td>{{ o.refunded_at ? 'Rembourse' : o.settled_at ? 'Reverse' : 'A venir' }}</td></tr></tbody>
    </table></div>
</template>
