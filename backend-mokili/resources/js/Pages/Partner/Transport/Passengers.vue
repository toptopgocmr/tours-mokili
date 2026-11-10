<script setup>
// Feuille d'embarquement : passagers d'un depart (a imprimer ou a montrer au chauffeur).
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    offer: { type: Object, required: true },
    bookings: { type: Array, default: () => [] },
});

const PAID = ['confirmed', 'paid', 'completed'];
const paid = computed(() => props.bookings.filter((b) => PAID.includes(b.status)));
const seats = computed(() => paid.value.reduce((s, b) => s + Number(b.quantity), 0));
const total = computed(() => paid.value.reduce((s, b) => s + Number(b.total_amount), 0));
const when = (iso) => (iso ? new Date(iso).toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) : '');
const label = (s) => ({ confirmed: 'Paye', paid: 'Paye', completed: 'Termine', awaiting_payment: 'En attente de paiement' }[s] ?? s);
</script>

<template>
    <Head title="Passagers" />

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 print:hidden">
        <div>
            <Link href="/partner/transport" class="text-sm text-slate-500 hover:text-slate-900">← Mes trajets</Link>
            <h1 class="text-xl font-bold text-slate-900">Passagers · {{ offer.origin_city }} → {{ offer.destination_city }}</h1>
            <p class="text-sm text-slate-500">{{ when(offer.departure_at) }} · {{ offer.transport_mode_label }}<span v-if="offer.departure_point"> · {{ offer.departure_point }}</span></p>
        </div>
        <button type="button" class="btn-console-secondary" onclick="window.print()">Imprimer la liste</button>
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Places payees</p><p class="mt-1 text-2xl font-bold">{{ seats }}</p></div>
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Places restantes</p><p class="mt-1 text-2xl font-bold">{{ offer.seats_available }}</p></div>
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Montant encaisse</p><p class="mt-1 text-2xl font-bold">{{ Math.round(total).toLocaleString('fr-FR') }} {{ $cur(offer.currency) }}</p></div>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead><tr><th>#</th><th>Reference</th><th>Passager</th><th>Telephone</th><th>Places</th><th>Message</th><th>Statut</th></tr></thead>
            <tbody>
                <tr v-for="(b, i) in bookings" :key="b.id">
                    <td>{{ i + 1 }}</td>
                    <td class="font-mono text-xs">{{ b.reference }}</td>
                    <td class="font-medium text-slate-900">{{ b.user?.name }}</td>
                    <td>{{ b.user?.phone || b.user?.email }}</td>
                    <td class="font-semibold">{{ b.quantity }}</td>
                    <td class="max-w-xs truncate text-xs text-slate-500">{{ b.notes }}</td>
                    <td><span class="console-badge" :class="PAID.includes(b.status) ? 'console-badge-success' : 'console-badge-pending'">{{ label(b.status) }}</span></td>
                </tr>
                <tr v-if="!bookings.length"><td colspan="7" class="py-8 text-center text-slate-400">Aucun passager pour ce depart.</td></tr>
            </tbody>
        </table>
    </div>
</template>
