<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ range: Object, byPartner: Array, unsettled: Array, refunds: Array });
const r = reactive({ ...props.range });
const apply = () => router.get('/admin/fret/finance', r, { preserveState: true });
const fmt = (n) => Number(n ?? 0).toLocaleString('fr-FR');

const selected = ref([]);
const form = useForm({ order_ids: [], reference: '' });
const settle = () => {
    form.order_ids = selected.value;
    form.post('/admin/fret/finance/reverser', { preserveScroll: true, onSuccess: () => { selected.value = []; form.reset('reference'); } });
};
</script>

<template>
    <Head title="Finance fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Finance fret</h1>
    <div class="console-panel mb-4 flex items-end gap-2 text-sm">
        <label>Du<input v-model="r.from" type="date" class="ml-1 rounded border-slate-300" /></label>
        <label>Au<input v-model="r.to" type="date" class="ml-1 rounded border-slate-300" /></label>
        <button class="btn-console-primary" @click="apply">Afficher</button>
    </div>

    <div class="console-panel mb-4 overflow-x-auto"><p class="mb-2 font-semibold">Par partenaire (periode)</p><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th>Partenaire</th><th>Devise</th><th class="text-right">Commandes</th><th class="text-right">Facture</th><th class="text-right">Commission</th><th class="text-right">Du au partenaire</th><th class="text-right">Reverse</th></tr></thead>
        <tbody><tr v-for="(p, i) in byPartner" :key="i" class="border-t"><td class="py-1">{{ p.partner?.name ?? '-' }}</td><td>{{ p.currency }}</td><td class="text-right">{{ p.orders }}</td><td class="text-right">{{ fmt(p.billed) }}</td><td class="text-right">{{ fmt(p.commission) }}</td><td class="text-right">{{ fmt(p.partner_due) }}</td><td class="text-right">{{ fmt(p.settled) }}</td></tr></tbody>
    </table></div>

    <div class="console-panel mb-4 overflow-x-auto"><p class="mb-2 font-semibold">Reversements a effectuer (reservations confirmees)</p>
        <table class="w-full text-sm"><tbody><tr v-for="o in unsettled" :key="o.id" class="border-t"><td class="py-1"><input v-model="selected" type="checkbox" :value="o.id" /></td><td>{{ o.reference }}</td><td>{{ o.partner?.name }}</td><td class="text-right">{{ fmt(o.partner_amount) }} {{ o.currency }}</td></tr></tbody></table>
        <form class="mt-3 flex gap-2" @submit.prevent="settle"><input v-model="form.reference" placeholder="Reference du virement / reversement" class="flex-1 rounded border-slate-300 text-sm" required /><button class="btn-console-primary" :disabled="!selected.length || form.processing">Marquer reverse ({{ selected.length }})</button></form>
    </div>

    <div class="console-panel text-sm"><p class="mb-2 font-semibold">Derniers remboursements</p><p v-for="o in refunds" :key="o.id">{{ o.reference }} - {{ fmt(o.total_amount) }} {{ o.currency }} - {{ o.refunded_at?.slice(0, 10) }}</p></div>
</template>
