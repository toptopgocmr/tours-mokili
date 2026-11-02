<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { money, STATUS_STYLES } from '@/utils/flights';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    dossiers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
});

const f = reactive({ q: props.filters.q ?? '', status: props.filters.status ?? '', attention: props.filters.attention ?? '' });
const apply = () => router.get('/admin/vols', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, replace: true });
const quick = (status, attention = '') => { f.status = status; f.attention = attention; apply(); };
</script>

<template>
    <Head title="Billetterie aerienne" />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-slate-900">Billetterie aerienne</h1>
        <Link href="/admin/vols/tarifs" class="btn-console-secondary">Tarifs & fournisseurs</Link>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-6">
        <button class="console-stat text-left" @click="quick('', '1')"><p class="text-xs text-slate-500">A traiter (alertes)</p><p class="text-2xl font-bold text-red-600">{{ stats.attention }}</p></button>
        <button class="console-stat text-left" @click="quick('BOOKING_PENDING')"><p class="text-xs text-slate-500">A reserver (PNR)</p><p class="text-2xl font-bold">{{ stats.toBook }}</p></button>
        <button class="console-stat text-left" @click="quick('TICKETING_PENDING')"><p class="text-xs text-slate-500">A emettre</p><p class="text-2xl font-bold">{{ stats.toTicket }}</p></button>
        <button class="console-stat text-left" @click="quick('TICKETED')"><p class="text-xs text-slate-500">Billets emis</p><p class="text-2xl font-bold text-green-700">{{ stats.ticketed }}</p></button>
        <div class="console-stat"><p class="text-xs text-slate-500">Apres-vente ouverts</p><p class="text-2xl font-bold">{{ stats.afterSales }}</p></div>
        <button class="console-stat text-left" @click="quick('REFUND_PENDING')"><p class="text-xs text-slate-500">Remboursements</p><p class="text-2xl font-bold">{{ stats.refunds }}</p></button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <input v-model="f.q" type="text" placeholder="Reference, PNR, nom, telephone..." class="w-72 rounded border-slate-300 text-sm" @keyup.enter="apply" />
        <select v-model="f.status" class="rounded border-slate-300 text-sm" @change="apply">
            <option value="">Tous les statuts</option>
            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ key }} - {{ label }}</option>
        </select>
        <label class="flex items-center gap-1.5 text-sm"><input v-model="f.attention" type="checkbox" true-value="1" false-value="" @change="apply" /> Alertes uniquement</label>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Dossier</th><th>Client</th><th>Trajet</th><th>Depart</th><th>Montant</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="d in dossiers.data" :key="d.id" :class="d.needs_attention && 'bg-red-50/60'">
                    <td class="font-mono text-xs">{{ d.reference }}<span v-if="d.pnr" class="block text-slate-500">PNR {{ d.pnr }}</span></td>
                    <td>{{ d.contact_name ?? d.user?.name }}<span class="block text-xs text-slate-500">{{ d.contact_phone }}</span></td>
                    <td>{{ d.origin }} {{ d.trip_type === 'roundtrip' ? '⇄' : '→' }} {{ d.destination }} <span class="text-xs text-slate-500">({{ d.passenger_count }} pax)</span></td>
                    <td>{{ d.depart_date ? new Date(d.depart_date).toLocaleDateString('fr-FR') : '' }}</td>
                    <td>{{ money(d.total_amount, d.currency) }}</td>
                    <td>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="STATUS_STYLES[d.status]">{{ d.status }}</span>
                        <span v-if="d.needs_attention" class="mt-1 block text-xs text-red-700">⚠ {{ d.attention_reason }}</span>
                    </td>
                    <td class="text-right"><Link :href="`/admin/vols/${d.reference}`" class="font-semibold text-[#0972D3] hover:underline">Ouvrir</Link></td>
                </tr>
                <tr v-if="!dossiers.data.length"><td colspan="7" class="py-6 text-center text-slate-400">Aucun dossier.</td></tr>
            </tbody>
        </table>
    </div>
    <Paginator :page="dossiers" />
</template>
