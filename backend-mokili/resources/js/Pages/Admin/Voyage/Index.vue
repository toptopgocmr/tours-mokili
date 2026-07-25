<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const filter = () => router.get('/admin/voyage', { search: search.value }, { preserveState: true });

const remove = (offer) => {
    if (confirm(`Supprimer l'offre "${offer.title}" ?`)) {
        router.delete(`/admin/voyage/${offer.id}`);
    }
};
</script>

<template>
    <Head title="Offres Voyage" />

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Offres Voyage</h1>
        <Link href="/admin/voyage/creer" class="btn-gold !py-2 text-sm">+ Nouvelle offre</Link>
    </div>

    <input v-model="search" type="text" placeholder="Rechercher..." class="mt-4 w-64 rounded-lg border-gray-300 text-sm" @keyup.enter="filter" />

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr>
                    <th class="px-4 py-3">Titre</th>
                    <th class="px-4 py-3">Destination</th>
                    <th class="px-4 py-3">Prix</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="o in offers.data" :key="o.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ o.title }}</td>
                    <td class="px-4 py-3">{{ o.destination_city }}</td>
                    <td class="px-4 py-3">{{ Number(o.price).toLocaleString('fr-FR') }} {{ o.currency }}</td>
                    <td class="px-4 py-3">
                        <span :class="o.is_active ? 'text-green-700' : 'text-gray-400'">{{ o.is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="space-x-3 px-4 py-3 text-right">
                        <Link :href="`/admin/voyage/${o.id}/editer`" class="font-semibold text-gold-600">Editer</Link>
                        <button class="font-semibold text-red-600" @click="remove(o)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!offers.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucune offre.</td></tr>
            </tbody>
        </table>
    </div>
</template>
