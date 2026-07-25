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
</script>

<template>
    <Head title="Tableau de bord" />

    <h1 class="text-2xl font-bold text-navy-900">Tableau de bord</h1>

    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div v-for="c in cards" :key="c.key" class="rounded-2xl border bg-white p-4">
            <p class="text-2xl font-bold text-navy-900">{{ stats[c.key] }}</p>
            <p class="text-xs text-navy-500">{{ c.label }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border bg-white p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-navy-900">Dernieres reservations</h2>
            <Link href="/admin/reservations" class="text-sm font-semibold text-gold-600">Voir tout →</Link>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="text-navy-500">
                <tr>
                    <th class="pb-2">Reference</th>
                    <th class="pb-2">Client</th>
                    <th class="pb-2">Montant</th>
                    <th class="pb-2">Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="b in recentBookings" :key="b.id" class="border-t">
                    <td class="py-2 font-medium">{{ b.reference }}</td>
                    <td class="py-2">{{ b.user?.name }}</td>
                    <td class="py-2">{{ Number(b.total_amount).toLocaleString('fr-FR') }} {{ b.currency }}</td>
                    <td class="py-2 capitalize">{{ b.status }}</td>
                </tr>
                <tr v-if="!recentBookings.length"><td colspan="4" class="py-4 text-center text-navy-400">Aucune reservation.</td></tr>
            </tbody>
        </table>
    </div>
</template>
