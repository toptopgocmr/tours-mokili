<script setup>
// Reservations : billets vendus sur les trajets / offres du partenaire.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    bookings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    rubriques: { type: Object, default: () => ({}) },
});

const TABS = [['', 'Toutes'], ['payees', 'Payees'], ['en_attente', 'En attente'], ['annulees', 'Annulees']];
const go = (q) => router.get('/partner/reservations', Object.fromEntries(Object.entries({ statut: props.filters.statut, rubrique: props.filters.rubrique, ...q }).filter(([, v]) => v)), { preserveState: true });
const pick = (s) => go({ statut: s || undefined });
const STATUS = {
    confirmed: ['Payee', 'console-badge-success'], paid: ['Payee', 'console-badge-success'], completed: ['Terminee', 'console-badge-success'],
    awaiting_payment: ['En attente', 'console-badge-pending'], cancelled: ['Annulee', 'console-badge-neutral'],
    refunded: ['Remboursee', 'console-badge-neutral'], failed: ['Echec', 'console-badge-error'],
};
const st = (s) => STATUS[s] ?? [s, 'console-badge-neutral'];
const money = (v, c = 'XAF') => `${Math.round(Number(v || 0)).toLocaleString('fr-FR')} ${c}`;
const when = (iso) => (iso ? new Date(`${iso}:00`).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');
</script>

<template>
    <Head title="Reservations" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Reservations</h1>
        <p class="text-sm text-slate-500">Billets, nuitees, locations, prestations et entrees vendus sur vos offres.</p>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Ventes du mois</p><p class="mt-1 text-2xl font-bold">{{ money(stats.month_revenue) }}</p></div>
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Places vendues (mois)</p><p class="mt-1 text-2xl font-bold">{{ stats.month_seats ?? 0 }}</p></div>
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">En attente de paiement</p><p class="mt-1 text-2xl font-bold">{{ stats.awaiting ?? 0 }}</p></div>
        <div class="console-stat"><p class="text-xs uppercase text-slate-500">Reservations payees</p><p class="mt-1 text-2xl font-bold">{{ stats.total_paid ?? 0 }}</p></div>
    </div>

    <div v-if="Object.keys(rubriques).length > 1" class="mb-3 flex flex-wrap gap-1.5">
        <button type="button" class="rounded border px-3 py-1 text-sm" :class="!filters.rubrique ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white'" @click="go({ rubrique: undefined })">Toutes les rubriques</button>
        <button v-for="(label, key) in rubriques" :key="key" type="button" class="rounded border px-3 py-1 text-sm" :class="filters.rubrique === key ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white'" @click="go({ rubrique: key })">{{ label }}</button>
    </div>
    <div class="mb-4 inline-flex rounded border border-slate-300 bg-white p-0.5">
        <button v-for="[k, l] in TABS" :key="k" type="button" class="rounded px-3 py-1.5 text-sm font-medium" :class="(filters.statut || '') === k ? 'bg-[#0F1B2A] text-white' : 'text-slate-700 hover:bg-slate-50'" @click="pick(k)">{{ l }}</button>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead><tr><th>Reservee le</th><th>Reference</th><th>Offre</th><th>Date</th><th>Client</th><th>Quantite</th><th>Montant</th><th>Statut</th></tr></thead>
            <tbody>
                <tr v-for="b in bookings.data" :key="b.id">
                    <td class="whitespace-nowrap">{{ new Date(b.created_at).toLocaleDateString('fr-FR') }}</td>
                    <td class="font-mono text-xs">{{ b.reference }}</td>
                    <td>
                        <p class="font-medium text-slate-900">{{ b.item?.title || '—' }}</p>
                        <p class="text-xs text-slate-500"><span class="font-semibold">{{ b.item?.kind }}</span><span v-if="b.item?.mode"> · {{ b.item.mode }}</span></p>
                    </td>
                    <td class="whitespace-nowrap">{{ when(b.item?.departure_at) }}<span v-if="b.item?.until" class="block text-xs text-slate-500">au {{ new Date(`${b.item.until}T12:00:00`).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}</span></td>
                    <td>
                        <p class="font-medium text-slate-900">{{ b.client?.name }}</p>
                        <p class="text-xs text-slate-500">{{ b.client?.phone || b.client?.email }}</p>
                        <div v-if="b.driver" class="mt-1 text-[11px] text-slate-500">
                            <p>{{ b.driver.id }} · <a :href="b.driver.id_url" target="_blank" class="font-semibold text-sky-700 underline">voir</a></p>
                            <p>{{ b.driver.license }}<template v-if="b.driver.license_url"> · <a :href="b.driver.license_url" target="_blank" class="font-semibold text-sky-700 underline">voir</a></template></p>
                        </div>
                    </td>
                    <td><span class="font-semibold">{{ b.item?.kind === 'Parc' && b.item?.unit?.includes('adulte') ? '' : b.quantity }}</span> <span class="text-xs text-slate-500">{{ b.item?.unit }}</span></td>
                    <td class="whitespace-nowrap font-semibold">{{ money(b.total, b.currency) }}</td>
                    <td><span class="console-badge" :class="st(b.status)[1]">{{ st(b.status)[0] }}</span></td>
                </tr>
                <tr v-if="!bookings.data.length"><td colspan="8" class="py-8 text-center text-slate-400">Aucune reservation pour le moment.</td></tr>
            </tbody>
        </table>
    </div>
    <div v-if="bookings.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link v-for="l in bookings.links" :key="l.label" :href="l.url || '#'" class="rounded border px-3 py-1 text-sm" :class="[l.active ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white', !l.url && 'pointer-events-none opacity-40']" v-html="l.label" />
    </div>
</template>
