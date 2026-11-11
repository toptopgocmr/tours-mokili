<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

// Demandes "Reserver ma visite" (Tourisme > Visite) : rappeler le client,
// puis passer la demande en Confirmee / Terminee / Annulee.
const props = defineProps({
    requests: { type: Object, required: true },
    statuses: { type: Object, default: () => ({}) },
    formulas: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
});

const filter = (status) => router.get('/admin/visites', status ? { status } : {}, { preserveState: true, replace: true });
const setStatus = (r, status) => router.patch(`/admin/visites/${r.id}`, { status }, { preserveScroll: true });
const day = (d) => (d ? new Date(d).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : '');
const badge = (s) => ({ nouvelle: 'console-badge console-badge-pending', confirmee: 'console-badge console-badge-success', terminee: 'console-badge console-badge-neutral', annulee: 'console-badge console-badge-error' }[s] ?? 'console-badge console-badge-neutral');
const wa = (phone) => `https://wa.me/${String(phone).replace(/\D/g, '')}`;
</script>

<template>
    <Head title="Demandes de visite" />
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Demandes de visite</h1>
            <p class="text-sm text-slate-500">Reservations "sans engagement" des sites touristiques. Rappelez le client puis mettez a jour le statut.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold" :class="!filters.status ? 'bg-navy-900 text-white' : 'bg-white'" @click="filter('')">Toutes</button>
            <button v-for="(l, k) in statuses" :key="k" type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold" :class="filters.status === k ? 'bg-navy-900 text-white' : 'bg-white'" @click="filter(k)">
                {{ l }} <span class="opacity-60">({{ counts[k] ?? 0 }})</span>
            </button>
        </div>

        <div class="overflow-x-auto rounded-2xl border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Site</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Groupe</th><th class="px-4 py-3">Message</th><th class="px-4 py-3">Statut</th></tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="r in requests.data" :key="r.id" class="align-top">
                        <td class="px-4 py-3"><Link v-if="r.business" :href="`/partenaires/${r.business.slug}`" class="font-semibold text-navy-900 hover:underline">{{ r.business.name }}</Link><p class="text-xs text-slate-500">{{ r.business?.city }}</p></td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ day(r.visit_date) }}</td>
                        <td class="px-4 py-3"><p class="font-semibold">{{ r.name }}</p><a :href="`tel:${r.phone}`" class="text-emerald-700">{{ r.phone }}</a> · <a :href="wa(r.phone)" target="_blank" rel="noopener" class="text-emerald-700">WhatsApp</a><p v-if="r.email" class="text-xs text-slate-500">{{ r.email }}</p></td>
                        <td class="whitespace-nowrap px-4 py-3"><template v-if="r.service"><b>RDV garage</b> · {{ r.service }}<span v-if="r.vehicle" class="block text-xs text-slate-500">{{ r.vehicle }}</span></template><template v-else>{{ r.people }} pers. · {{ formulas[r.formula] ?? r.formula }}</template><span v-if="r.visit_time" class="block text-xs text-slate-500">a {{ r.visit_time }}</span></td>
                        <td class="max-w-xs px-4 py-3 text-slate-600">{{ r.message }}</td>
                        <td class="px-4 py-3">
                            <span :class="badge(r.status)">{{ statuses[r.status] ?? r.status }}</span>
                            <select class="mt-2 block rounded-lg border-slate-300 text-xs" :value="r.status" @change="setStatus(r, $event.target.value)">
                                <option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr v-if="!requests.data.length"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Aucune demande pour le moment.</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="requests.last_page > 1" class="flex justify-center gap-1">
            <Link v-for="l in requests.links" :key="l.label" :href="l.url || '#'" class="rounded-lg border px-3 py-1.5 text-sm" :class="[l.active && 'bg-navy-900 text-white', !l.url && 'pointer-events-none opacity-40']" v-html="l.label" />
        </div>
    </div>
</template>
