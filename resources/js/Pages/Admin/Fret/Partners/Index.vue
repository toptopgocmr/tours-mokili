<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ partners: Object, filters: { type: Object, default: () => ({}) }, statusLabels: Object });
const filter = (e) => router.get('/admin/fret/partenaires', e.target.value ? { status: e.target.value } : {}, { preserveState: true });
</script>

<template>
    <Head title="Partenaires fret" />
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Partenaires fret</h1>
        <select class="rounded border-slate-300 text-sm" :value="filters.status ?? ''" @change="filter">
            <option value="">Tous</option><option v-for="(l, k) in statusLabels" :key="k" :value="k">{{ l }}</option>
        </select>
    </div>
    <div class="console-panel overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th>Societe</th><th>Contact</th><th>Modes</th><th>Statut</th><th></th></tr></thead>
        <tbody><tr v-for="p in partners.data" :key="p.id" class="border-t">
            <td class="py-2 font-medium">{{ p.company_name }}</td><td>{{ p.user?.email }}</td><td>{{ (p.modes ?? []).join(', ') }}</td><td>{{ statusLabels[p.status] }}</td>
            <td class="text-right"><Link :href="`/admin/fret/partenaires/${p.id}`" class="text-blue-600">Examiner</Link></td></tr></tbody>
    </table><p v-if="!partners.data.length" class="py-6 text-center text-slate-500">Aucun partenaire.</p></div>
</template>
