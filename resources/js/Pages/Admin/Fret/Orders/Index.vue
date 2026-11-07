<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ orders: Object, filters: { type: Object, default: () => ({}) }, statuses: Object });
const f = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '', stalled: !!props.filters.stalled });
const apply = () => router.get('/admin/fret/commandes', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
</script>

<template>
    <Head title="Commandes fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Commandes de fret</h1>
    <div class="console-panel mb-4 flex flex-wrap gap-2">
        <input v-model="f.search" placeholder="Reference" class="rounded border-slate-300 text-sm" @keyup.enter="apply" />
        <select v-model="f.status" class="rounded border-slate-300 text-sm" @change="apply"><option value="">Tous statuts</option><option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option></select>
        <label class="flex items-center gap-1 text-sm"><input v-model="f.stalled" type="checkbox" @change="apply" /> Sans suivi depuis 3 j</label>
        <button class="btn-console-primary" @click="apply">Filtrer</button>
    </div>
    <div class="console-panel overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th>Reference</th><th>Client</th><th>Partenaire</th><th>Statut</th><th>Suivi</th><th class="text-right">Montant</th><th></th></tr></thead>
        <tbody><tr v-for="o in orders.data" :key="o.id" class="border-t">
            <td class="py-2 font-medium">{{ o.reference }}</td><td>{{ o.user?.name }}</td><td>{{ o.partner?.name }}</td><td>{{ o.status }}</td><td>{{ o.shipment?.tracking_code }} ({{ o.shipment?.status }})</td>
            <td class="text-right">{{ Number(o.total_amount).toLocaleString('fr-FR') }} {{ o.currency }}</td>
            <td class="text-right"><Link :href="`/admin/fret/commandes/${o.reference}`" class="text-blue-600">Ouvrir</Link></td></tr></tbody>
    </table><p v-if="!orders.data.length" class="py-6 text-center text-slate-500">Aucune commande.</p></div>
</template>
