<script setup>
// Programme des vols integre a l'onglet Vols : panneau des departs /
// arrivees d'un aeroport (AirLabs), charge sans quitter la page.
import AirlineLogo from '@/Components/AirlineLogo.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    airport: { type: String, default: 'BZV' },
    limit: { type: Number, default: 12 },
});

const QUICK = ['BZV', 'PNR', 'FIH', 'DLA', 'LBV', 'ADD', 'CDG', 'DXB', 'IST', 'JNB'];
const form = reactive({ airport: props.airport, to: '', direction: 'departures' });
const state = reactive({ loading: false, flights: [], error: null, airports: {} });
const showAll = ref(false);
const arrivals = computed(() => form.direction === 'arrivals');
const city = (code) => state.airports?.[code]?.city ?? code;
const shown = computed(() => (showAll.value ? state.flights : state.flights.slice(0, props.limit)));

async function load() {
    const code = (form.airport || '').toUpperCase();
    if (code.length !== 3) return;
    state.loading = true;
    showAll.value = false;
    try {
        const q = new URLSearchParams({ airport: code, direction: form.direction, ...(form.to.length === 3 ? { to: form.to.toUpperCase() } : {}) });
        const res = await fetch(`/api/flights/schedules?${q}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(String(res.status));
        const data = await res.json();
        Object.assign(state, { flights: data.flights ?? [], error: data.error ?? null, airports: data.airports ?? {} });
    } catch {
        Object.assign(state, { flights: [], error: 'unavailable' });
    } finally {
        state.loading = false;
    }
}
const pick = (code) => { form.airport = code; load(); };
const setDirection = (d) => { form.direction = d; load(); };
onMounted(load);

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
const fullLink = computed(() => `/voyage/vols/horaires?airport=${form.airport}&direction=${form.direction}${form.to ? `&to=${form.to}` : ''}`);
</script>

<template>
    <div>
        <form class="flex flex-wrap items-end gap-3" @submit.prevent="load">
            <div class="inline-flex rounded-xl bg-navy-50 p-1">
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="!arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="setDirection('departures')">🛫 Departs</button>
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="setDirection('arrivals')">🛬 Arrivees</button>
            </div>
            <label class="flex flex-col text-xs font-semibold text-navy-500">
                Aeroport
                <input v-model="form.airport" maxlength="3" class="mt-1 w-24 rounded-lg border-navy-200 px-3 py-2 text-sm font-bold uppercase text-navy-900" @input="form.airport = form.airport.toUpperCase()" />
            </label>
            <label class="flex flex-col text-xs font-semibold text-navy-500">
                {{ arrivals ? 'En provenance de' : 'Vers' }} (optionnel)
                <input v-model="form.to" maxlength="3" placeholder="ex. CDG" class="mt-1 w-24 rounded-lg border-navy-200 px-3 py-2 text-sm font-bold uppercase text-navy-900" @input="form.to = form.to.toUpperCase()" />
            </label>
            <button class="rounded-lg bg-gold-500 px-5 py-2 text-sm font-bold text-navy-900 hover:bg-gold-400">Afficher</button>
        </form>
        <div class="mt-3 flex flex-wrap gap-2">
            <button v-for="c in QUICK" :key="c" type="button" class="rounded-full px-3 py-1 text-xs font-semibold" :class="c === form.airport ? 'bg-navy-900 text-white' : 'bg-navy-50 text-navy-700 hover:bg-navy-100'" @click="pick(c)">{{ c }}</button>
        </div>

        <div v-if="state.loading" class="mt-4 space-y-2">
            <div v-for="i in 4" :key="i" class="h-14 animate-pulse rounded-xl bg-navy-50" />
        </div>
        <div v-else-if="state.error === 'not_configured'" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Le programme des vols en direct sera disponible tres bientot.
        </div>
        <div v-else-if="state.error" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            Programme momentanement indisponible. Reessayez dans quelques minutes.
        </div>
        <div v-else-if="!state.flights.length" class="mt-4 rounded-xl border bg-white p-6 text-center text-sm text-navy-500">
            Aucun vol programme pour le moment sur cette selection.
        </div>

        <div v-else class="mt-4 overflow-hidden rounded-2xl border bg-white">
            <div class="hidden grid-cols-[90px_1fr_1.4fr_110px] gap-3 bg-navy-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gold-400 sm:grid">
                <span>Heure</span><span>Vol</span><span>{{ arrivals ? 'Provenance' : 'Destination' }}</span><span class="text-right">Statut</span>
            </div>
            <div v-for="(f, i) in shown" :key="f.flight + (f.departure_at ?? i)" class="grid grid-cols-[70px_1fr_auto] items-center gap-3 border-t px-4 py-3 first:border-t-0 sm:grid-cols-[90px_1fr_1.4fr_110px]">
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
                    <p class="text-xs text-navy-500"><span v-if="f.terminal">Terminal {{ f.terminal }}</span><span v-if="f.gate"> · Porte {{ f.gate }}</span></p>
                </div>
                <div class="text-right">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="status(f)[1]">{{ status(f)[0] }}</span>
                </div>
            </div>
            <div class="flex items-center justify-between border-t bg-navy-50/50 px-4 py-2 text-sm">
                <button v-if="state.flights.length > limit && !showAll" type="button" class="font-semibold text-sky-700 hover:underline" @click="showAll = true">Voir les {{ state.flights.length }} vols</button>
                <span v-else />
                <Link :href="fullLink" class="font-semibold text-navy-700 hover:underline">Plein ecran →</Link>
            </div>
        </div>
        <p class="mt-2 text-xs text-navy-400">Horaires indicatifs : verifiez aupres de la compagnie avant de vous rendre a l'aeroport.</p>
    </div>
</template>
