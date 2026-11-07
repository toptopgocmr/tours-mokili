<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    orders: Object, filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] }, anomalies: { type: Number, default: 0 },
});
const f = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '', anomaly: !!props.filters.anomaly });
const apply = () => router.get('/admin/vols', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, replace: true });
</script>

<template>
    <Head title="Vols" />
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Dossiers de vol</h1>
        <span v-if="anomalies" class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">{{ anomalies }} anomalie(s)</span>
    </div>
    <div class="console-panel mb-4 flex flex-wrap gap-2">
        <input v-model="f.search" placeholder="Reference, PNR, email" class="rounded border-slate-300 text-sm" @keyup.enter="apply" />
        <select v-model="f.status" class="rounded border-slate-300 text-sm" @change="apply">
            <option value="">Tous statuts</option>
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <label class="flex items-center gap-1 text-sm"><input v-model="f.anomaly" type="checkbox" @change="apply" /> Anomalies</label>
        <button class="btn-console-primary" @click="apply">Filtrer</button>
    </div>
    <div class="console-panel overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th>Reference</th><th>Client</th><th>Statut</th><th>PNR</th><th class="text-right">Montant</th><th></th></tr></thead>
            <tbody>
                <tr v-for="o in orders.data" :key="o.id" class="border-t">
                    <td class="py-2 font-medium">{{ o.reference }}</td>
                    <td>{{ o.user?.name }}<br /><span class="text-xs text-slate-500">{{ o.contact_email }}</span></td>
                    <td>{{ o.status }} <span v-if="o.is_anomaly" class="ml-1 rounded bg-red-100 px-1 text-xs text-red-700">anomalie</span></td>
                    <td>{{ o.pnr ?? '-' }}</td>
                    <td class="text-right">{{ Number(o.price_total).toLocaleString('fr-FR') }} {{ o.currency }}</td>
                    <td class="text-right"><Link :href="`/admin/vols/${o.reference}`" class="text-blue-600">Ouvrir</Link></td>
                </tr>
            </tbody>
        </table>
        <p v-if="!orders.data.length" class="py-6 text-center text-slate-500">Aucun dossier.</p>
    </div>
</template>
