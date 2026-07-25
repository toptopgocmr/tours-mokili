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
</script>

<template>
    <Head title="Reservations" />

    <h1 class="text-2xl font-bold text-navy-900">Toutes les reservations</h1>
    <p class="mt-1 text-sm text-navy-500">Tous modules confondus (Voyage, Logement, Voiture, Divertissement, Marketplace).</p>

    <select v-model="status" class="mt-4 rounded-lg border-gray-300 text-sm" @change="filter">
        <option value="">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="awaiting_payment">Attente paiement</option>
        <option value="confirmed">Confirmee</option>
        <option value="cancelled">Annulee</option>
        <option value="completed">Terminee</option>
    </select>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Montant</th>
                    <th class="px-4 py-3">Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="b in bookings.data" :key="b.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ b.reference }}</td>
                    <td class="px-4 py-3">{{ b.user?.name }}</td>
                    <td class="px-4 py-3">{{ b.bookable_type?.split('\\').pop() }}</td>
                    <td class="px-4 py-3">{{ Number(b.total_amount).toLocaleString('fr-FR') }} {{ b.currency }}</td>
                    <td class="px-4 py-3 capitalize">{{ b.status }}</td>
                </tr>
                <tr v-if="!bookings.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucune reservation.</td></tr>
            </tbody>
        </table>
    </div>
</template>
