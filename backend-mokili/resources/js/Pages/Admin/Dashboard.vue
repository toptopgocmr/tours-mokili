<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stats: { type: Object, required: true },
    recentBookings: { type: Array, default: () => [] },
});

const cards = [
    { key: 'users', label: 'Clients' },
    { key: 'partners', label: 'Partenaires' },
    { key: 'agents', label: 'Agents' },
    { key: 'travelOffers', label: 'Offres Voyage' },
    { key: 'bookings', label: 'Reservations' },
    { key: 'bookingsConfirmed', label: 'Confirmees' },
];

const badgeClass = (status) => ({
    confirmed: 'console-badge console-badge-success',
    awaiting_payment: 'console-badge console-badge-pending',
    pending: 'console-badge console-badge-pending',
    cancelled: 'console-badge console-badge-error',
}[status] ?? 'console-badge console-badge-neutral');
</script>

<template>
    <Head title="Tableau de bord" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Tableau de bord</h1>
        <p class="text-sm text-slate-500">Vue d'ensemble de l'activite MOKILI TOUR.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div v-for="c in cards" :key="c.key" class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ c.label }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats[c.key] }}</p>
        </div>
    </div>

    <div class="mt-6 console-panel">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Dernieres reservations</h2>
            <Link href="/admin/reservations" class="text-sm font-semibold text-[#0972D3] hover:underline">Voir tout &rarr;</Link>
        </div>
        <table class="console-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Client</th>
                    <th>Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="b in recentBookings" :key="b.id">
                    <td class="font-medium text-slate-900">{{ b.reference }}</td>
                    <td>{{ b.user?.name }}</td>
                    <td>{{ Number(b.total_amount).toLocaleString('fr-FR') }} {{ b.currency }}</td>
                    <td><span :class="badgeClass(b.status)">{{ b.status }}</span></td>
                </tr>
                <tr v-if="!recentBookings.length"><td colspan="4" class="py-4 text-center text-slate-400">Aucune reservation.</td></tr>
            </tbody>
        </table>
    </div>
</template>
