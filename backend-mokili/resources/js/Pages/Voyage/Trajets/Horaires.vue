<script setup>
// Programme des departs / arrivees des trains, bus, canots rapides et taxis
// (panneau facon gare), construit a partir des trajets saisis dans l'admin.
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { RIDE_ICONS } from '@/utils/rides';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    city: { type: String, default: 'Brazzaville' },
    direction: { type: String, default: 'departures' },
    mode: { type: String, default: '' },
    date: { type: String, default: '' },
    rides: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    zone: { type: String, default: 'congo' },
    world: { type: Object, default: () => ({}) },
});

const isWorld = computed(() => props.zone === 'monde');
const setZone = (z) => router.get('/voyage/trajets/horaires', z === 'monde' ? { zone: 'monde' } : {}, { preserveScroll: true });

// ------------------------------------------------------------ MONDE (Transitland)
const W_MODES = [
    { value: '', label: 'Tous', icon: '🧭' },
    { value: 'train', label: 'Trains', icon: '🚆' },
    { value: 'bus', label: 'Bus / cars', icon: '🚌' },
    { value: 'metro', label: 'Metro / tram', icon: '🚇' },
    { value: 'ferry', label: 'Ferries', icon: '⛴️' },
];
const W_COLORS = { train: '#0369A1', bus: '#059669', metro: '#7C3AED', ferry: '#0891B2' };
const W_QUICK = ['Paris Gare de Lyon', 'Bruxelles-Midi', "London King's Cross", 'Amsterdam Centraal', 'Berlin Hbf', 'Madrid Atocha', 'New York Penn Station', 'Montreal Gare Centrale'];
const q = ref(props.world?.gare_name || '');
const results = ref([]);
const searching = ref(false);
const searchError = ref(null);
let timer = null;
const searchStations = () => {
    clearTimeout(timer);
    const text = q.value.trim();
    if (text.length < 2) { results.value = []; return; }
    timer = setTimeout(async () => {
        searching.value = true;
        try {
            const { data } = await axios.get('/voyage/trajets/gares', { params: { q: text } });
            results.value = data.stations ?? [];
            searchError.value = data.error;
        } catch {
            results.value = [];
            searchError.value = 'unavailable';
        } finally {
            searching.value = false;
        }
    }, 250);
};
const pickStation = (st) => {
    results.value = [];
    q.value = st.name;
    goWorld({ gare: st.id, nom: st.name });
};
const quickStation = async (name) => {
    q.value = name;
    searching.value = true;
    try {
        const { data } = await axios.get('/voyage/trajets/gares', { params: { q: name } });
        const first = (data.stations ?? [])[0];
        if (first) pickStation(first);
        else { results.value = []; searchError.value = data.error ?? 'none'; }
    } finally {
        searching.value = false;
    }
};
function goWorld(params = {}) {
    const w = props.world ?? {};
    const query = { zone: 'monde', gare: w.gare, nom: w.gare_name, mode: w.mode || undefined, date: w.date || undefined, ...params };
    router.get('/voyage/trajets/horaires', Object.fromEntries(Object.entries(query).filter(([, v]) => v)), { preserveScroll: true });
}
const wStatus = (d) => {
    if (d.cancelled) return ['Supprime', 'bg-rose-50 text-rose-700'];
    if (d.delay_minutes > 0) return [`Retard +${d.delay_minutes} min`, 'bg-amber-50 text-amber-700'];
    if (d.realtime) return ['A l\'heure', 'bg-emerald-50 text-emerald-700'];
    return ['Prevu', 'bg-navy-50 text-navy-700'];
};

const form = reactive({ city: props.city });
const arrivals = computed(() => props.direction === 'arrivals');

const MODES = [
    { value: '', label: 'Tous', icon: '🧭' },
    { value: 'train', label: 'Trains', icon: '🚆' },
    { value: 'bus', label: 'Bus', icon: '🚌' },
    { value: 'canot', label: 'Canots rapides', icon: '🚤' },
    { value: 'taxi', label: 'Taxis', icon: '🚕' },
];
const MODE_COLORS = { train: 'bg-sky-700', bus: 'bg-emerald-600', canot: 'bg-cyan-600', taxi: 'bg-amber-500' };
const QUICK = ['Brazzaville', 'Pointe-Noire', 'Dolisie', 'Kinshasa', 'Oyo', 'Ouesso'];

// 7 prochains jours (heure du Congo).
const todayIso = (() => {
    const d = new Date(new Date().toLocaleString('en-US', { timeZone: 'Africa/Brazzaville' }));
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
})();
const days = computed(() => Array.from({ length: 7 }, (_, i) => {
    const d = new Date(`${todayIso}T12:00:00`);
    d.setDate(d.getDate() + i);
    const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    return { iso, label: i === 0 ? "Aujourd'hui" : i === 1 ? 'Demain' : d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) };
}));

function go(params = {}) {
    const q = { city: form.city, direction: props.direction, mode: props.mode || undefined, date: props.date !== todayIso ? props.date : undefined, ...params };
    router.get('/voyage/trajets/horaires', Object.fromEntries(Object.entries(q).filter(([, v]) => v)), { preserveScroll: true });
}
const pickCity = (c) => { form.city = c; go({ city: c }); };

const time = (v) => (v ? v.slice(11, 16) : '--:--');
const dayLabel = computed(() => {
    const d = new Date(`${props.date}T12:00:00`);
    const s = d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
    return s.charAt(0).toUpperCase() + s.slice(1);
});
const STATUS = {
    scheduled: ['A l\'heure', 'bg-navy-50 text-navy-700'],
    boarding: ['Embarquement', 'bg-gold-100 text-gold-700'],
    en_route: ['En route', 'bg-sky-50 text-sky-700'],
    departed: ['Parti', 'bg-gray-100 text-gray-600'],
    arrived: ['Arrive', 'bg-emerald-50 text-emerald-700'],
    full: ['Complet', 'bg-rose-50 text-rose-700'],
};
const status = (r) => STATUS[r.status] ?? STATUS.scheduled;
const counts = computed(() => Object.fromEntries(MODES.map((m) => [m.value, m.value ? props.rides.filter((r) => r.mode === m.value).length : props.rides.length])));
</script>

<template>
    <Head :title="`Programme des departs - ${city}`" />

    <PhotoHero class="px-4 pb-10 pt-8 text-white">
        <div class="mx-auto max-w-5xl">
            <Link href="/voyage?type=circuit" class="text-sm text-navy-100 hover:text-white">← Rechercher un trajet</Link>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Programme des departs</h1>
            <p class="mt-1 text-navy-100">
                {{ isWorld ? 'Trains, bus, metros et ferries du monde entier : prochains departs, voies et retards.' : 'Trains, bus, canots rapides et taxis au Congo : horaires du jour, places et statut en direct.' }}
            </p>

            <!-- Congo / Monde -->
            <div class="mt-5 inline-flex rounded-xl bg-white/15 p-1 backdrop-blur">
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="!isWorld ? 'bg-white text-navy-900 shadow' : 'text-white hover:bg-white/10'" @click="setZone('congo')">🇨🇬 Congo · nos transporteurs</button>
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="isWorld ? 'bg-white text-navy-900 shadow' : 'text-white hover:bg-white/10'" @click="setZone('monde')">🌍 Monde · gares et lignes</button>
            </div>

            <!-- MONDE : recherche de gare -->
            <div v-if="isWorld" class="mt-4">
                <div class="relative max-w-xl">
                    <label class="text-xs font-semibold text-navy-100">Gare ou arret</label>
                    <input
                        v-model="q"
                        type="text"
                        placeholder="ex. Paris Gare de Lyon, Bruxelles-Midi, Johannesburg..."
                        class="mt-1 w-full rounded-lg border-0 px-4 py-3 text-sm font-semibold text-navy-900 focus:ring-2 focus:ring-gold-500"
                        @input="searchStations"
                    />
                    <span v-if="searching" class="absolute right-3 top-9 text-xs text-navy-400">Recherche...</span>
                    <ul v-if="results.length" class="absolute z-20 mt-1 max-h-80 w-full overflow-auto rounded-xl bg-white py-1 text-navy-900 shadow-xl">
                        <li v-for="st in results" :key="st.id">
                            <button type="button" class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-navy-50" @click="pickStation(st)">
                                <span class="text-lg">{{ st.kind === 'Gare' ? '🚉' : '🚏' }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold">{{ st.name }}</span>
                                    <span class="block truncate text-xs text-navy-500">{{ [st.kind, st.region, st.country].filter(Boolean).join(' · ') }}</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="n in W_QUICK" :key="n" type="button" class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur hover:bg-white/25" @click="quickStation(n)">{{ n }}</button>
                </div>
            </div>

            <form v-else class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="go()">
                <label class="flex flex-col text-xs font-semibold text-navy-100">
                    {{ arrivals ? 'Arrivees a' : 'Departs de' }}
                    <select v-model="form.city" class="mt-1 w-64 max-w-full rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm font-semibold text-navy-900 focus:ring-2 focus:ring-gold-500" @change="go({ city: form.city })">
                        <option v-if="!cities.includes(form.city)" :value="form.city">{{ form.city }}</option>
                        <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                    </select>
                </label>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 font-bold text-navy-900 hover:bg-gold-400">Afficher</button>
            </form>
            <div v-if="!isWorld" class="mt-3 flex flex-wrap gap-2">
                <button v-for="c in QUICK" :key="c" type="button" class="rounded-full px-3 py-1 text-xs font-semibold backdrop-blur" :class="c === city ? 'bg-gold-500 text-navy-900' : 'bg-white/15 hover:bg-white/25'" @click="pickCity(c)">{{ c }}</button>
            </div>
        </div>
    </PhotoHero>

    <!-- ===================== MONDE ===================== -->
    <section v-if="isWorld" class="mx-auto max-w-5xl px-4 py-6">
        <div v-if="!world.configured" class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <p class="font-bold">Les horaires du monde entier arrivent tres bientot.</p>
            <p class="mt-1 text-sm">En attendant, consultez le programme de nos transporteurs au Congo.</p>
            <button type="button" class="mt-3 rounded-full bg-navy-900 px-5 py-2 text-sm font-semibold text-white" @click="setZone('congo')">Voir le Congo</button>
        </div>
        <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">{{ world.station ? 'Departs' : 'Choisissez une gare' }}</p>
                    <h2 class="text-xl font-extrabold text-navy-900">{{ world.station?.name || world.gare_name || 'Gares du monde entier' }}</h2>
                    <p v-if="world.station?.country" class="text-sm text-navy-500">{{ world.station.country }}<span v-if="world.station.timezone"> · heure locale ({{ world.station.timezone }})</span></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="m in W_MODES"
                        :key="m.value"
                        type="button"
                        class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-semibold"
                        :class="(world.mode || '') === m.value ? 'border-navy-900 bg-navy-900 text-white' : 'border-navy-100 text-navy-700 hover:border-navy-400'"
                        :disabled="!world.gare"
                        @click="goWorld({ mode: m.value || undefined })"
                    >
                        <span>{{ m.icon }}</span>{{ m.label }}
                    </button>
                </div>
            </div>

            <div v-if="world.gare" class="mt-4 flex gap-2 overflow-x-auto pb-1">
                <button type="button" class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold" :class="!world.date ? 'border-sky-600 bg-sky-600 text-white' : 'border-navy-100 bg-white text-navy-800'" @click="goWorld({ date: undefined })">Maintenant</button>
                <button
                    v-for="d in days.slice(1)"
                    :key="d.iso"
                    type="button"
                    class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold"
                    :class="world.date === d.iso ? 'border-sky-600 bg-sky-600 text-white' : 'border-navy-100 bg-white text-navy-800 hover:border-sky-400'"
                    @click="goWorld({ date: d.iso })"
                >
                    {{ d.label }}
                </button>
            </div>

            <div v-if="!world.gare" class="mt-5 rounded-2xl border border-navy-100 bg-white p-8 text-center">
                <p class="text-3xl">🌍</p>
                <p class="mt-2 font-bold text-navy-900">Recherchez une gare ou un arret</p>
                <p class="mt-1 text-sm text-navy-600">Europe, Amerique du Nord, Asie, Oceanie... des milliers de reseaux de trains, bus, metros et ferries.</p>
            </div>
            <div v-else-if="world.error" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-700">
                Horaires momentanement indisponibles pour cette gare. Reessayez dans quelques minutes.
            </div>
            <div v-else-if="!world.departures.length" class="mt-5 rounded-2xl border border-navy-100 bg-white p-8 text-center">
                <p class="font-bold text-navy-900">Aucun depart trouve {{ world.date ? 'ce jour-la' : 'dans les prochaines heures' }}</p>
                <p class="mt-1 text-sm text-navy-600">Essayez un autre jour, un autre moyen de transport ou la gare principale de la ville.</p>
            </div>
            <div v-else class="mt-4 overflow-hidden rounded-2xl border border-navy-100 bg-white shadow-sm">
                <div class="hidden grid-cols-[90px_120px_1.6fr_1fr_70px_130px] gap-3 bg-navy-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gold-400 md:grid">
                    <span>Heure</span><span>Ligne</span><span>Destination</span><span>Operateur</span><span>Voie</span><span class="text-right">Statut</span>
                </div>
                <div
                    v-for="(d, i) in world.departures"
                    :key="i"
                    class="grid grid-cols-[62px_1fr_auto] items-center gap-3 border-t border-navy-100 px-4 py-3 first:border-t-0 md:grid-cols-[90px_120px_1.6fr_1fr_70px_130px]"
                    :class="d.cancelled && 'opacity-60'"
                >
                    <div>
                        <p class="text-lg font-extrabold tabular-nums text-navy-900" :class="d.cancelled && 'line-through'">{{ d.time }}</p>
                        <p v-if="d.estimated && d.estimated !== d.time" class="text-xs font-semibold text-amber-700">{{ d.estimated }}</p>
                    </div>
                    <div class="min-w-0 md:contents">
                        <div>
                            <span class="inline-flex max-w-full items-center gap-1 truncate rounded-md px-2 py-0.5 text-xs font-bold" :style="{ background: d.color || W_COLORS[d.family], color: d.text_color || '#fff' }">
                                {{ W_MODES.find((m) => m.value === d.family)?.icon }} {{ d.line || '—' }}
                            </span>
                        </div>
                        <p class="mt-1 truncate font-semibold text-navy-900 md:mt-0">{{ d.headsign || d.line_name || '—' }}</p>
                        <p class="truncate text-xs text-navy-500 md:text-sm md:text-navy-700">{{ d.operator }}</p>
                        <p class="hidden text-sm font-semibold text-navy-900 md:block">{{ d.platform || '—' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold" :class="wStatus(d)[1]">{{ wStatus(d)[0] }}</span>
                        <p v-if="d.platform" class="mt-1 text-xs text-navy-500 md:hidden">Voie {{ d.platform }}</p>
                    </div>
                </div>
            </div>
            <p class="mt-4 text-xs text-navy-500">
                Donnees <a href="https://www.transit.land/terms" target="_blank" rel="noopener" class="font-semibold underline">Transitland</a>, d'apres les horaires publies par les reseaux. Heures locales de la gare. Verifiez aupres de l'operateur avant de partir.
            </p>
        </template>
    </section>

    <!-- ===================== CONGO ===================== -->
    <section v-else class="mx-auto max-w-5xl px-4 py-6">
        <!-- Departs / Arrivees + moyens -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-xl bg-navy-50 p-1">
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="!arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="go({ direction: 'departures' })">Departs</button>
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" :class="arrivals ? 'bg-white text-navy-900 shadow' : 'text-navy-600'" @click="go({ direction: 'arrivals' })">Arrivees</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="m in MODES"
                    :key="m.value"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-semibold transition"
                    :class="mode === m.value ? 'border-navy-900 bg-navy-900 text-white' : 'border-navy-100 text-navy-700 hover:border-navy-400'"
                    @click="go({ mode: m.value || undefined })"
                >
                    <span>{{ m.icon }}</span>{{ m.label }}
                </button>
            </div>
        </div>

        <!-- Jours -->
        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
            <button
                v-for="d in days"
                :key="d.iso"
                type="button"
                class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold"
                :class="date === d.iso ? 'border-sky-600 bg-sky-600 text-white' : 'border-navy-100 bg-white text-navy-800 hover:border-sky-400'"
                @click="go({ date: d.iso })"
            >
                {{ d.label }}
            </button>
        </div>

        <p class="mt-4 text-sm text-navy-600">
            <strong class="text-navy-900">{{ dayLabel }}</strong> · {{ arrivals ? 'Arrivees a' : 'Departs de' }} {{ city }} · {{ rides.length }} trajet{{ rides.length > 1 ? 's' : '' }}
        </p>

        <div v-if="!rides.length" class="mt-4 rounded-2xl border border-navy-100 bg-white p-8 text-center">
            <p class="text-3xl">{{ MODES.find((m) => m.value === mode)?.icon ?? '🧭' }}</p>
            <p class="mt-2 font-bold text-navy-900">Aucun {{ arrivals ? 'arrivee' : 'depart' }} programme ce jour-la</p>
            <p class="mt-1 text-sm text-navy-600">Essayez un autre jour, une autre ville ou un autre moyen de transport.</p>
        </div>

        <!-- Prochains departs (jours suivants) -->
        <div v-if="!rides.length && upcoming.length" class="mt-5">
            <h3 class="mb-2 text-lg font-extrabold text-navy-900">Prochains {{ arrivals ? 'arrivees' : 'departs' }} {{ arrivals ? 'a' : 'de' }} {{ city }}</h3>
            <div class="overflow-hidden rounded-2xl border border-navy-100 bg-white">
                <component
                    :is="r.bookable ? Link : 'div'"
                    v-for="r in upcoming"
                    :key="`u${r.id}`"
                    :href="r.bookable ? `/voyage/${r.slug}` : undefined"
                    class="flex items-center gap-4 border-t border-navy-100 px-4 py-3 first:border-t-0 hover:bg-sky-50/50"
                >
                    <div class="w-24 shrink-0">
                        <p class="text-xs font-semibold uppercase text-sky-700">{{ new Date(`${r.departure_at}:00`).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) }}</p>
                        <p class="text-lg font-extrabold tabular-nums text-navy-900">{{ time(r.departure_at) }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold text-white" :class="MODE_COLORS[r.mode] ?? 'bg-navy-700'">{{ RIDE_ICONS[r.mode] ?? '🧭' }} {{ r.mode_label }}</span>
                        <p class="mt-1 truncate font-semibold text-navy-900">{{ r.operator || 'Transporteur partenaire' }} · {{ arrivals ? r.origin : r.destination }}</p>
                    </div>
                    <p class="hidden text-sm font-semibold text-navy-900 sm:block">{{ Number(r.price).toLocaleString('fr-FR') }} {{ r.currency }}</p>
                    <span v-if="r.bookable" class="text-sm font-semibold text-sky-700">Reserver ›</span>
                </component>
            </div>
        </div>

        <div v-if="rides.length" class="mt-3 overflow-hidden rounded-2xl border border-navy-100 bg-white shadow-sm">
            <div class="hidden grid-cols-[80px_1.2fr_1.3fr_110px_120px] gap-3 bg-navy-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gold-400 md:grid">
                <span>Heure</span><span>Transport</span><span>{{ arrivals ? 'Provenance' : 'Destination' }}</span><span>Places</span><span class="text-right">Statut</span>
            </div>
            <component
                :is="r.bookable ? Link : 'div'"
                v-for="r in rides"
                :key="r.id"
                :href="r.bookable ? `/voyage/${r.slug}` : undefined"
                class="grid grid-cols-[64px_1fr_auto] items-center gap-3 border-t border-navy-100 px-4 py-3 first:border-t-0 md:grid-cols-[80px_1.2fr_1.3fr_110px_120px]"
                :class="r.bookable ? 'transition hover:bg-sky-50/50' : 'opacity-80'"
            >
                <div>
                    <p class="text-lg font-extrabold tabular-nums text-navy-900">{{ time(arrivals ? r.arrival_at || r.departure_at : r.departure_at) }}</p>
                    <p v-if="arrivals && r.departure_at" class="text-[11px] text-navy-400">dep. {{ time(r.departure_at) }}</p>
                    <p v-else-if="!arrivals && r.arrival_at" class="text-[11px] text-navy-400">arr. {{ time(r.arrival_at) }}</p>
                </div>
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold text-white" :class="MODE_COLORS[r.mode] ?? 'bg-navy-700'">{{ RIDE_ICONS[r.mode] ?? '🧭' }} {{ r.mode_label }}</span>
                    <p class="mt-1 truncate font-semibold text-navy-900">{{ r.operator || 'Transporteur partenaire' }}</p>
                    <p class="truncate text-xs text-navy-500 md:hidden">{{ arrivals ? r.origin : r.destination }}</p>
                </div>
                <div class="hidden min-w-0 md:block">
                    <p class="truncate font-semibold text-navy-900">{{ arrivals ? r.origin : r.destination }}</p>
                    <p v-if="!arrivals && r.departure_point" class="truncate text-xs text-navy-500">Depart : {{ r.departure_point }}</p>
                </div>
                <div class="hidden md:block">
                    <p class="text-sm font-semibold" :class="r.seats === 0 ? 'text-rose-600' : r.seats <= 3 ? 'text-amber-700' : 'text-emerald-700'">{{ r.seats === 0 ? 'Complet' : `${r.seats} place${r.seats > 1 ? 's' : ''}` }}</p>
                    <p class="text-xs text-navy-500">{{ Number(r.price).toLocaleString('fr-FR') }} {{ r.currency }}</p>
                </div>
                <div class="text-right">
                    <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold" :class="status(r)[1]">{{ status(r)[0] }}</span>
                    <p v-if="r.bookable" class="mt-1 text-xs font-semibold text-sky-700">Reserver ›</p>
                </div>
            </component>
        </div>

        <p class="mt-4 text-xs text-navy-500">Horaires communiques par les transporteurs. Presentez-vous en avance au point de depart.</p>
    </section>
</template>
