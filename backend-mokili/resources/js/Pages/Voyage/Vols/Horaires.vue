<script setup>
// Programme des vols en direct (AirLabs) : tableau des departs / arrivees
// d'un aeroport, facon panneau d'aeroport, avec logos des compagnies,
// statut et retards.
import MainLayout from '@/Layouts/MainLayout.vue';
import AirlineLogo from '@/Components/AirlineLogo.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    airport: { type: String, default: 'BZV' },
    to: { type: String, default: null },
    direction: { type: String, default: 'departures' },
    flights: { type: Array, default: () => [] },
    error: { type: String, default: null },
    airports: { type: Object, default: () => ({}) },
});

const QUICK = ['BZV', 'PNR', 'FIH', 'DLA', 'LBV', 'ADD', 'CDG', 'DXB', 'IST', 'JNB'];
const form = reactive({ airport: props.airport, to: props.to ?? '' });
const arrivals = computed(() => props.direction === 'arrivals');
const city = (code) => props.airports?.[code]?.city ?? code;

function go(params = {}) {
    const q = { airport: form.airport, to: form.to || undefined, direction: props.direction, ...params };
    router.get('/voyage/vols/horaires', Object.fromEntries(Object.entries(q).filter(([, v]) => v)), { preserveScroll: true });
}
function pick(code) {
    form.airport = code;
    go({ airport: code });
}

const time = (v) => (v ? v.slice(11, 16) : '--:--');
const day = (v) => (v ? new Date(v.replace(' ', 'T')).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) : '');
const STATUS = {
    scheduled: ['Prevu', 'bg-navy-50 text-navy-700'],
    active: ['En vol', 'bg-sky-50 text-sky-700'],
    landed: ['Atterri', 'bg-emerald-50 text-emerald-700'],
    cancelled: ['Annule', 'bg-rose-50 text-rose-700'],
    diverted: ['Detourne', 'bg-amber-50 text-amber-700'],
    incident: ['Incident', 'bg-rose-50 text-rose-700'],
};
const status = (f) => STATUS[f.status] ?? [f.status, 'bg-navy-50 text-navy-700'];
</script>

<template>
    <Head :title="`Programme des vols - ${city(airport)}`" />

    <section class="bg-navy-900 px-4 pb-10 pt-8 text-white">
        <div class="mx-auto max-w-5xl">
            <Link href="/voyage?type=vol" class="text-sm text-navy-200 hover:text-white">← Rechercher un vol</Link>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Programme des vols</h1>
            <p class="mt-1 text-navy-200">Departs et arrivees en direct, mis a jour regulierement.</p>

            <form class="mt-5 flex flex-wrap items-end gap-3" @submit.prevent="go()">
                <label class="flex flex-col text-xs font-semibold text-navy-200">
                    Aeroport (code IATA)
                    <input v-model="form.airport" maxlength="3" class="mt-1 w-32 rounded-lg border-0 px-3 py-2.5 text-base font-bold uppercase text-navy-900" @input="form.airport = form.airport.toUpperCase()" />
                </label>
                <label class="flex flex-col text-xs font-semibold text-navy-200">
                    {{ arrivals ? 'En provenance de' : 'Vers' }} (optionnel)
                    <input v-model="form.to" maxlength="3" placeholder="ex. CDG" class="mt-1 w-32 rounded-lg border-0 px-3 py-2.5 text-base font-bold uppercase text-navy-900" @input="form.to = form.to.toUpperCase()" />
                </label>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 font-bold text-navy-900 hover:bg-gold-400">Afficher</button>
            </form>
            <div class="mt-3 flex flex-wrap gap-2">
                <button v-for="c in QUICK" :key="c" type="button" class="rounded-full px-3 py-1 text-xs font-semibold" :class="c === airport ? 'bg-gold-500 text-navy-900' : 'bg-white/10 hover:bg-white/20'" @click="pick(c)">{{ c }}</button>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-6">
        <div class="flex items-center justify-between gap-3">
            <div class="inline-flex rounded-xl bg-navy-50 p-1">
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="!arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="go({ direction: 'departures' })">🛫 Departs</button>
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="go({ direction: 'arrivals' })">🛬 Arrivees</button>
            </div>
            <p class="text-sm text-navy-500">{{ city(airport) }} ({{ airport }}) · {{ flights.length }} vol{{ flights.length > 1 ? 's' : '' }}</p>
        </div>

        <div v-if="error === 'not_configured'" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
            Le programme des vols sera disponible tres bientot.
        </div>
        <div v-else-if="error" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-700">
            Programme momentanement indisponible. Reessayez dans quelques minutes.
        </div>
        <div v-else-if="!flights.length" class="mt-6 rounded-xl border bg-white p-8 text-center text-navy-500">
            Aucun vol programme pour le moment sur cette selection.
        </div>

        <div v-else class="mt-4 overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="hidden grid-cols-[90px_1fr_1.4fr_110px] gap-3 bg-navy-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gold-400 sm:grid">
                <span>Heure</span><span>Vol</span><span>{{ arrivals ? 'Provenance' : 'Destination' }}</span><span class="text-right">Statut</span>
            </div>
            <div v-for="(f, i) in flights" :key="f.flight + (f.departure_at ?? i)" class="grid grid-cols-[70px_1fr_auto] items-center gap-3 border-t px-4 py-3 first:border-t-0 sm:grid-cols-[90px_1fr_1.4fr_110px]">
                <div>
                    <p class="text-lg font-extrabold text-navy-900">{{ time(arrivals ? f.arrival_at : f.departure_at) }}</p>
                    <p v-if="f.delay_minutes > 0" class="text-xs font-semibold text-rose-600">{{ time(arrivals ? f.arrival_estimated : f.departure_estimated) }} (+{{ f.delay_minutes }} min)</p>
                    <p class="text-[11px] text-navy-400">{{ day(arrivals ? f.arrival_at : f.departure_at) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <AirlineLogo :code="f.airline_code" size="h-8 w-8 text-[10px]" />
                    <div>
                        <p class="font-bold text-navy-900">{{ f.flight }}</p>
                        <p class="text-xs text-navy-500 sm:hidden">{{ arrivals ? city(f.origin) : city(f.destination) }}</p>
                    </div>
                </div>
                <div class="hidden sm:block">
                    <p class="font-semibold text-navy-900">{{ arrivals ? city(f.origin) : city(f.destination) }} <span class="text-navy-400">({{ arrivals ? f.origin : f.destination }})</span></p>
                    <p class="text-xs text-navy-500">
                        <span v-if="f.terminal">Terminal {{ f.terminal }}</span>
                        <span v-if="f.gate"> · Porte {{ f.gate }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="status(f)[1]">{{ status(f)[0] }}</span>
                </div>
            </div>
        </div>

        <p class="mt-4 text-xs text-navy-400">Donnees AirLabs. Les horaires peuvent changer : verifiez aupres de la compagnie avant de vous rendre a l'aeroport.</p>
    </section>
</template>
