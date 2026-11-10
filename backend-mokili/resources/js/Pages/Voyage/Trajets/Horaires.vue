<script setup>
// Programme des departs / arrivees des trains, bus, canots rapides et taxis
// (panneau facon gare), construit a partir des trajets saisis dans l'admin.
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { RIDE_ICONS } from '@/utils/rides';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    city: { type: String, default: 'Brazzaville' },
    direction: { type: String, default: 'departures' },
    mode: { type: String, default: '' },
    date: { type: String, default: '' },
    rides: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
});

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
            <p class="mt-1 text-navy-100">Trains, bus, canots rapides et taxis : horaires du jour, places et statut en direct.</p>

            <form class="mt-5 flex flex-wrap items-end gap-3" @submit.prevent="go()">
                <label class="flex flex-col text-xs font-semibold text-navy-100">
                    {{ arrivals ? 'Arrivees a' : 'Departs de' }}
                    <select v-model="form.city" class="mt-1 w-64 max-w-full rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm font-semibold text-navy-900 focus:ring-2 focus:ring-gold-500" @change="go({ city: form.city })">
                        <option v-if="!cities.includes(form.city)" :value="form.city">{{ form.city }}</option>
                        <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                    </select>
                </label>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 font-bold text-navy-900 hover:bg-gold-400">Afficher</button>
            </form>
            <div class="mt-3 flex flex-wrap gap-2">
                <button v-for="c in QUICK" :key="c" type="button" class="rounded-full px-3 py-1 text-xs font-semibold backdrop-blur" :class="c === city ? 'bg-gold-500 text-navy-900' : 'bg-white/15 hover:bg-white/25'" @click="pickCity(c)">{{ c }}</button>
            </div>
        </div>
    </PhotoHero>

    <section class="mx-auto max-w-5xl px-4 py-6">
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

        <div v-else class="mt-3 overflow-hidden rounded-2xl border border-navy-100 bg-white shadow-sm">
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
