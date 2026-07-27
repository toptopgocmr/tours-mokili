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

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Offres Voyage</h1>
        <Link href="/admin/voyage/creer" class="btn-console-primary">+ Nouvelle offre</Link>
    </div>

    <input v-model="search" type="text" placeholder="Rechercher..." class="mb-4 w-64 rounded border-slate-300 text-sm" @keyup.enter="filter" />

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Destination</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="o in offers.data" :key="o.id">
                    <td class="font-medium text-slate-900">{{ o.title }}</td>
                    <td>{{ o.destination_city }}</td>
                    <td>{{ Number(o.price).toLocaleString('fr-FR') }} {{ o.currency }}</td>
                    <td>
                        <span :class="o.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ o.is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/admin/voyage/${o.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(o)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!offers.data.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucune offre.</td></tr>
            </tbody>
        </table>
    </div>
</template>
