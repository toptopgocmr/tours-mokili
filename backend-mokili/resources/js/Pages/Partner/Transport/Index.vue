<script setup>
// Espace partenaire : trajets (bus, train, canots, taxis) et offres vols
// du partenaire, avec places vendues, promotions et actions.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    offers: { type: Object, required: true },
    tab: { type: String, default: 'a_venir' },
    filters: { type: Object, default: () => ({}) },
    verified: { type: Boolean, default: true },
    stats: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');
const TABS = [
    { key: 'a_venir', label: 'Trajets a venir' },
    { key: 'passes', label: 'Trajets passes' },
    { key: 'vols', label: 'Offres vols' },
];
const go = (params) => router.get('/partner/transport', Object.fromEntries(Object.entries({ vue: props.tab !== 'a_venir' ? props.tab : undefined, q: q.value || undefined, ...params }).filter(([, v]) => v)), { preserveState: true });

const remove = (o) => {
    if (confirm(`Supprimer « ${o.origin_city} → ${o.destination_city} » ?\nS'il a des reservations, il sera seulement masque.`)) {
        router.delete(`/partner/transport/${o.id}`, { preserveScroll: true });
    }
};
const when = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return `${d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' })} ${d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' })}`;
};
const promoOn = (o) => Number(o.discount_percent) > 0;
</script>

<template>
    <Head :title="tab === 'vols' ? 'Mes offres vols' : 'Mes trajets'" />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ tab === 'vols' ? 'Mes offres vols' : 'Trajets & horaires' }}</h1>
            <p class="text-sm text-slate-500">{{ stats.upcoming ?? 0 }} depart(s) a venir · {{ stats.flights ?? 0 }} offre(s) vol · {{ stats.promos ?? 0 }} en promotion</p>
        </div>
        <div class="flex gap-2">
            <Link href="/partner/transport/creer?type=vol" class="btn-console-secondary">+ Offre vol</Link>
            <Link href="/partner/transport/creer" class="btn-console-primary">+ Nouveau trajet</Link>
        </div>
    </div>

    <div v-if="!verified" class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
        Votre compte est en cours de validation : vos offres sont enregistrees mais pas encore visibles par les clients.
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="inline-flex rounded border border-slate-300 bg-white p-0.5">
            <button
                v-for="t in TABS"
                :key="t.key"
                type="button"
                class="rounded px-3 py-1.5 text-sm font-medium"
                :class="tab === t.key ? 'bg-[#0F1B2A] text-white' : 'text-slate-700 hover:bg-slate-50'"
                @click="go({ vue: t.key === 'a_venir' ? undefined : t.key })"
            >
                {{ t.label }}
            </button>
        </div>
        <input v-model="q" type="text" placeholder="Ville de depart ou d'arrivee..." class="rounded border-slate-300 text-sm" @keyup.enter="go({})" />
        <button class="btn-console-secondary" @click="go({})">Filtrer</button>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>{{ tab === 'vols' ? 'Dates' : 'Depart' }}</th>
                    <th>Trajet</th>
                    <th>Moyen</th>
                    <th>Prix</th>
                    <th>Places</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="o in offers.data" :key="o.id">
                    <td class="whitespace-nowrap">{{ when(o.departure_at) }}</td>
                    <td>
                        <p class="font-medium text-slate-900">{{ o.origin_city || 'Brazzaville' }} → {{ o.destination_city }}</p>
                        <p class="text-xs text-slate-500">{{ o.departure_point || o.airline }}</p>
                    </td>
                    <td>{{ o.transport_mode_label }}</td>
                    <td class="whitespace-nowrap">
                        <span v-if="promoOn(o)" class="mr-1 text-xs text-slate-400 line-through">{{ Number(o.price).toLocaleString('fr-FR') }}</span>
                        <span class="font-semibold text-slate-900">{{ Math.round(o.discounted_price).toLocaleString('fr-FR') }} {{ $cur(o.currency) }}</span>
                        <span v-if="promoOn(o)" class="ml-1 console-badge console-badge-error">-{{ o.discount_percent }} %</span>
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="font-semibold">{{ o.seats_sold ?? 0 }}</span> vendue(s) · {{ o.seats_available }} restante(s)
                    </td>
                    <td>
                        <span v-if="o.is_active" class="console-badge console-badge-success">En vente</span>
                        <span v-else-if="!verified" class="console-badge console-badge-pending">En attente</span>
                        <span v-else class="console-badge console-badge-neutral">Masque</span>
                    </td>
                    <td class="space-x-3 whitespace-nowrap text-right">
                        <Link v-if="o.type === 'circuit'" :href="`/partner/transport/${o.id}/passagers`" class="font-semibold text-[#0972D3] hover:underline">Passagers ({{ o.bookings_count }})</Link>
                        <Link :href="`/partner/transport/${o.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <Link :href="`/partner/transport/creer?copie=${o.id}`" class="font-semibold text-slate-600 hover:underline">Dupliquer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(o)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!offers.data.length">
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        {{ tab === 'vols' ? 'Aucune offre vol.' : 'Aucun trajet.' }}
                        <Link :href="tab === 'vols' ? '/partner/transport/creer?type=vol' : '/partner/transport/creer'" class="font-semibold text-[#0972D3]">Creer maintenant</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div v-if="offers.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link v-for="l in offers.links" :key="l.label" :href="l.url || '#'" class="rounded border px-3 py-1 text-sm" :class="[l.active ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white', !l.url && 'pointer-events-none opacity-40']" v-html="l.label" />
    </div>
</template>
