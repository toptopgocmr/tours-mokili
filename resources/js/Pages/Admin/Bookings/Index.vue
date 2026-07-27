<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    bookings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const status = ref(props.filters.status ?? '');
const filter = () => router.get('/admin/reservations', { status: status.value }, { preserveState: true });

const badgeClass = (s) => ({
    confirmed: 'console-badge console-badge-success',
    completed: 'console-badge console-badge-success',
    awaiting_payment: 'console-badge console-badge-pending',
    pending: 'console-badge console-badge-pending',
    cancelled: 'console-badge console-badge-error',
}[s] ?? 'console-badge console-badge-neutral');
</script>

<template>
    <Head title="Reservations" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Toutes les reservations</h1>
        <p class="text-sm text-slate-500">Tous modules confondus (Voyage, Logement, Voiture, Divertissement, Marketplace).</p>
    </div>

    <select v-model="status" class="mb-4 rounded border-slate-300 text-sm" @change="filter">
        <option value="">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="awaiting_payment">Attente paiement</option>
        <option value="confirmed">Confirmee</option>
        <option value="cancelled">Annulee</option>
        <option value="completed">Terminee</option>
    </select>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Client</th>
                    <th>Module</th>
                    <th>Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="b in bookings.data" :key="b.id">
                    <td class="font-medium text-slate-900">{{ b.reference }}</td>
                    <td>{{ b.user?.name }}</td>
                    <td>{{ b.bookable_type?.split('\\').pop() }}</td>
                    <td>{{ Number(b.total_amount).toLocaleString('fr-FR') }} {{ b.currency }}</td>
                    <td><span :class="badgeClass(b.status)">{{ b.status }}</span></td>
                </tr>
                <tr v-if="!bookings.data.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucune reservation.</td></tr>
            </tbody>
        </table>
    </div>
</template>
