<script setup>
// Resultats de recherche de vols - mise en page Skyscanner :
// bandeau trajet avec dates (jour precedent / suivant), filtres a gauche
// (escales avec prix "a partir de", bagages, horaires, duree, aeroports
// d'escale, compagnies), onglets Les meilleurs / Les moins chers / Les plus
// rapides et cartes de vols. Les filtres s'appliquent instantanement.
import MainLayout from '@/Layouts/MainLayout.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import FlightOfferCard from '@/Components/FlightOfferCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { bestSort, duration, money, offerDuration, offerStops, routeLabel, stopCodes } from '@/utils/flights';

defineOptions({ layout: MainLayout });

const props = defineProps({
    search: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    offers: { type: Array, default: () => [] },
    airlines: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    expired: { type: Boolean, default: false },
    supplierErrors: { type: Object, default: null },
    totalCount: { type: Number, default: 0 },
    cabins: { type: Object, default: () => ({}) },
});

const city = (code) => props.airports?.[code]?.city ?? code;
const fmt = (n) => money(n, props.offers[0]?.currency ?? 'XAF');

// ------------------------------------------------------------- filtres
const STOP_GROUPS = [
    { key: 'direct', label: 'Direct', test: (o) => offerStops(o) === 0 },
    { key: 'one', label: '1 escale', test: (o) => offerStops(o) === 1 },
    { key: 'more', label: '2 escales et +', test: (o) => offerStops(o) >= 2 },
];
const maxDur = computed(() => Math.max(60, ...props.offers.map(offerDuration)));
const f = reactive({
    stops: props.filters?.direct === '1' ? ['direct'] : ['direct', 'one', 'more'],
    baggage: false,
    depFrom: 0,
    depTo: 24,
    maxDuration: null,
    noLayover: [],
    noAirline: [],
    sort: typeof props.filters?.sort === 'string' ? props.filters.sort : 'best',
});
const showFilters = ref(false);
const showForm = ref(false);
const limit = ref(15);

const depHour = (o) => Number(String(o.slices?.[0]?.departure_at ?? '').slice(11, 13)) || 0;
const layoversOf = (o) => [...new Set((o.slices ?? []).flatMap(stopCodes))];
const airlinesOf = (o) => [...new Set((o.slices ?? []).flatMap((s) => (s.segments ?? []).map((g) => g.airline_code)).filter(Boolean))];

// Toutes les conditions sauf une (pour calculer les "a partir de" de chaque groupe, comme Skyscanner).
const passes = (o, skip = '') =>
    (skip === 'stops' || STOP_GROUPS.some((g) => f.stops.includes(g.key) && g.test(o)))
    && (!f.baggage || !!o.fare_rules?.baggage)
    && depHour(o) >= f.depFrom && depHour(o) <= f.depTo
    && (!f.maxDuration || offerDuration(o) <= f.maxDuration)
    && (skip === 'layover' || !layoversOf(o).some((c) => f.noLayover.includes(c)))
    && (skip === 'airline' || !airlinesOf(o).some((c) => f.noAirline.includes(c)));

const filtered = computed(() => props.offers.filter((o) => passes(o)));
const minPrice = (list) => (list.length ? Math.min(...list.map((o) => Number(o.total_amount))) : null);

const stopStats = computed(() => STOP_GROUPS.map((g) => {
    const all = props.offers.filter(g.test);
    return { ...g, count: all.length, from: minPrice(props.offers.filter((o) => g.test(o) && passes(o, 'stops'))) };
}));
const layoverStats = computed(() => {
    const codes = [...new Set(props.offers.flatMap(layoversOf))].sort();
    return codes.map((c) => ({ code: c, name: city(c), from: minPrice(props.offers.filter((o) => layoversOf(o).includes(c) && passes(o, 'layover'))) }));
});
const airlineStats = computed(() => {
    const map = new Map();
    props.offers.forEach((o) => (o.slices ?? []).forEach((s) => (s.segments ?? []).forEach((g) => g.airline_code && map.set(g.airline_code, g.airline_name ?? g.airline_code))));
    if (!map.size) props.offers.forEach((o) => o.validating_airline && map.set(o.validating_airline, o.airline_name));
    return [...map.entries()].map(([code, name]) => ({ code, name, from: minPrice(props.offers.filter((o) => airlinesOf(o).includes(code) && passes(o, 'airline'))) }))
        .sort((a, b) => (a.from ?? Infinity) - (b.from ?? Infinity));
});
const toggle = (list, v) => { const i = list.indexOf(v); i >= 0 ? list.splice(i, 1) : list.push(v); limit.value = 15; };
const resetFilters = () => {
    f.stops = ['direct', 'one', 'more']; f.baggage = false; f.depFrom = 0; f.depTo = 24; f.maxDuration = null; f.noLayover = []; f.noAirline = [];
};
const activeFilters = computed(() => f.stops.length < 3 || f.baggage || f.depFrom > 0 || f.depTo < 24 || f.maxDuration || f.noLayover.length || f.noAirline.length);

// -------------------------------------------------------------- tri
const cheapestList = computed(() => [...filtered.value].sort((a, b) => Number(a.total_amount) - Number(b.total_amount)));
const fastestList = computed(() => [...filtered.value].sort((a, b) => offerDuration(a) - offerDuration(b)));
const bestList = computed(() => bestSort(filtered.value));
const sorted = computed(() => ({
    best: bestList.value,
    price: cheapestList.value,
    duration: fastestList.value,
    departure: [...filtered.value].sort((a, b) => String(a.slices?.[0]?.departure_at).localeCompare(String(b.slices?.[0]?.departure_at))),
}[f.sort] ?? bestList.value));
const avg = (list) => (list.length ? Math.round(list.slice(0, 5).reduce((t, o) => t + offerDuration(o), 0) / Math.min(5, list.length)) : 0);
const tabs = computed(() => [
    { key: 'best', label: 'Les meilleurs', offer: bestList.value[0], avg: avg(bestList.value) },
    { key: 'price', label: 'Les moins chers', offer: cheapestList.value[0], avg: avg(cheapestList.value) },
    { key: 'duration', label: 'Les plus rapides', offer: fastestList.value[0], avg: avg(fastestList.value) },
]);
const sortLabel = computed(() => ({ best: 'Les meilleurs', price: 'Les moins chers', duration: 'Les plus rapides', departure: 'Heure de depart' }[f.sort]));
const badgesFor = (o) => [
    o === cheapestList.value[0] && filtered.value.length > 1 ? 'Le moins cher' : null,
    o === fastestList.value[0] && filtered.value.length > 1 ? 'Le plus rapide' : null,
].filter(Boolean);

// ------------------------------------------------------------ favoris
const FAV_KEY = 'mokili_vols_favoris';
const readFav = () => { try { return JSON.parse(localStorage.getItem(FAV_KEY) ?? '[]'); } catch { return []; } };
const favorites = ref(readFav());
const toggleFav = (o) => {
    toggle(favorites.value, o.uuid);
    try { localStorage.setItem(FAV_KEY, JSON.stringify(favorites.value.slice(-50))); } catch { /* stockage indisponible */ }
};

// ------------------------------------------------------- recherche / dates
const isMulti = computed(() => props.search.trip_type === 'multicity' && (props.search.legs ?? []).length >= 2);
const title = computed(() => (isMulti.value
    ? routeLabel(props.search.legs, city)
    : `${city(props.search.origin)} (${props.search.origin}) - ${city(props.search.destination)} (${props.search.destination})`));
const paxText = computed(() => {
    const n = props.search.adults + props.search.children + props.search.infants;
    return `${n} voyageur${n > 1 ? 's' : ''}, ${props.cabins[props.search.cabin] ?? props.search.cabin}`;
});
const labels = computed(() => ({
    origin: `${city(props.search.origin)} (${props.search.origin})`,
    destination: `${city(props.search.destination)} (${props.search.destination})`,
}));
const day = (iso) => (iso ? String(iso).slice(0, 10) : null);
const dayText = (iso) => (iso ? new Date(`${day(iso)}T12:00:00`).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) : '');
const shift = (iso, n) => { const d = new Date(`${day(iso)}T12:00:00`); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
const today = new Date().toISOString().slice(0, 10);

const searching = ref(false);
const research = (overrides = {}) => router.get('/voyage/vols/recherche', {
    trip_type: props.search.trip_type,
    ...(isMulti.value
        ? { legs: props.search.legs }
        : { origin: props.search.origin, destination: props.search.destination, depart_date: day(props.search.depart_date), return_date: day(props.search.return_date) ?? undefined }),
    adults: props.search.adults, children: props.search.children, infants: props.search.infants, cabin: props.search.cabin,
    ...(f.stops.length === 1 && f.stops[0] === 'direct' ? { direct_only: 1 } : {}),
    ...overrides,
}, { onStart: () => (searching.value = true), onFinish: () => (searching.value = false) });
const moveDepart = (n) => {
    const d = shift(props.search.depart_date, n);
    const r = day(props.search.return_date);
    research({ depart_date: d, ...(r && r < d ? { return_date: d } : {}) });
};
const moveReturn = (n) => research({ return_date: shift(props.search.return_date, n) });
</script>

<template>
    <Head :title="`Vols ${title}`" />

    <!-- Bandeau recherche -->
    <section class="bg-navy-900 px-4 pb-5 pt-4 text-white sm:px-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-3 flex items-center gap-2 text-sm">
                <Link href="/voyage?type=vol" class="rounded-full bg-white/10 px-3 py-1 font-semibold hover:bg-white/20">✈ Vols</Link>
                <Link href="/voyage/vols/mes-billets" class="ml-auto text-white/80 hover:text-white">Mes billets d'avion →</Link>
            </div>
            <div class="flex flex-col gap-2 lg:flex-row">
                <button type="button" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg bg-white/10 px-3 py-2.5 text-left hover:bg-white/15" @click="showForm = !showForm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-sky-600 text-white">⌕</span>
                    <span class="min-w-0 flex-1 truncate text-center font-semibold">{{ title }} <span class="font-normal text-white/80">· {{ paxText }}</span></span>
                </button>
                <div v-if="!isMulti" class="flex items-stretch gap-2">
                    <button type="button" class="rounded-lg bg-white/10 px-3 hover:bg-white/20 disabled:opacity-40" :disabled="day(search.depart_date) <= today" title="Jour precedent" @click="moveDepart(-1)">‹</button>
                    <span class="flex min-w-[118px] items-center justify-center rounded-lg bg-white/10 px-3 py-2.5 text-sm font-semibold">{{ dayText(search.depart_date) }}</span>
                    <button type="button" class="rounded-lg bg-white/10 px-3 hover:bg-white/20" title="Jour suivant" @click="moveDepart(1)">›</button>
                    <template v-if="search.return_date">
                        <span class="self-center text-white/60">·</span>
                        <button type="button" class="rounded-lg bg-white/10 px-3 hover:bg-white/20 disabled:opacity-40" :disabled="day(search.return_date) <= day(search.depart_date)" title="Jour precedent" @click="moveReturn(-1)">‹</button>
                        <span class="flex min-w-[118px] items-center justify-center rounded-lg bg-white/10 px-3 py-2.5 text-sm font-semibold">{{ dayText(search.return_date) }}</span>
                        <button type="button" class="rounded-lg bg-white/10 px-3 hover:bg-white/20" title="Jour suivant" @click="moveReturn(1)">›</button>
                    </template>
                </div>
            </div>
            <div v-if="showForm" class="mt-3 rounded-2xl bg-white p-4 text-navy-900">
                <FlightSearchForm :initial="{ ...search, direct_only: f.stops.length === 1 && f.stops[0] === 'direct' }" :labels="labels" :cabins="cabins" :airports="airports" />
            </div>
        </div>
    </section>

    <div class="bg-navy-50/50">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[280px_1fr]">
            <!-- Filtres -->
            <aside class="space-y-5 text-sm" :class="showFilters ? 'block' : 'hidden lg:block'">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-bold text-navy-900">Filtres</p>
                    <button v-if="activeFilters" type="button" class="text-xs font-semibold text-navy-700 underline" @click="resetFilters">Tout effacer</button>
                </div>

                <section class="border-b pb-5">
                    <p class="mb-2 text-base font-bold text-navy-900">Escales</p>
                    <label v-for="g in stopStats" :key="g.key" class="flex cursor-pointer items-start gap-3 py-1.5" :class="!g.count && 'cursor-not-allowed opacity-50'">
                        <input type="checkbox" class="mt-0.5 h-5 w-5 rounded border-navy-300 text-sky-600 focus:ring-sky-500" :checked="f.stops.includes(g.key)" :disabled="!g.count" @change="toggle(f.stops, g.key)" />
                        <span>
                            <span class="block text-navy-900">{{ g.label }}</span>
                            <span class="text-xs text-navy-500">{{ g.from != null ? `a partir de ${fmt(g.from)}` : 'Aucun' }}</span>
                        </span>
                    </label>
                </section>

                <section class="border-b pb-5">
                    <p class="mb-2 text-base font-bold text-navy-900">Bagages</p>
                    <label class="flex cursor-pointer items-center gap-3 py-1">
                        <input v-model="f.baggage" type="checkbox" class="h-5 w-5 rounded border-navy-300 text-sky-600 focus:ring-sky-500" />
                        <span class="text-navy-900">🧳 Bagage en soute inclus</span>
                    </label>
                </section>

                <section class="border-b pb-5">
                    <p class="mb-1 text-base font-bold text-navy-900">Heure de depart (aller)</p>
                    <p class="mb-2 text-xs text-navy-500">{{ String(f.depFrom).padStart(2, '0') }}:00 - {{ f.depTo === 24 ? '23:59' : String(f.depTo).padStart(2, '0') + ':00' }}</p>
                    <input v-model.number="f.depFrom" type="range" min="0" max="24" step="1" class="w-full accent-sky-600" @input="f.depFrom > f.depTo && (f.depTo = f.depFrom)" />
                    <input v-model.number="f.depTo" type="range" min="0" max="24" step="1" class="w-full accent-sky-600" @input="f.depTo < f.depFrom && (f.depFrom = f.depTo)" />
                </section>

                <section class="border-b pb-5">
                    <p class="mb-1 text-base font-bold text-navy-900">Duree du voyage</p>
                    <p class="mb-2 text-xs text-navy-500">Jusqu'a {{ duration(f.maxDuration ?? maxDur) }}</p>
                    <input :value="f.maxDuration ?? maxDur" type="range" :min="60" :max="maxDur" step="15" class="w-full accent-sky-600" @input="f.maxDuration = Number($event.target.value) >= maxDur ? null : Number($event.target.value)" />
                </section>

                <section v-if="layoverStats.length" class="border-b pb-5">
                    <p class="mb-2 text-base font-bold text-navy-900">Aeroports d'escale</p>
                    <label v-for="l in layoverStats" :key="l.code" class="flex cursor-pointer items-start gap-3 py-1.5">
                        <input type="checkbox" class="mt-0.5 h-5 w-5 rounded border-navy-300 text-sky-600 focus:ring-sky-500" :checked="!f.noLayover.includes(l.code)" @change="toggle(f.noLayover, l.code)" />
                        <span>
                            <span class="block text-navy-900">{{ l.name }} ({{ l.code }})</span>
                            <span class="text-xs text-navy-500">{{ l.from != null ? `a partir de ${fmt(l.from)}` : '—' }}</span>
                        </span>
                    </label>
                </section>

                <section v-if="airlineStats.length" class="pb-5">
                    <p class="mb-2 text-base font-bold text-navy-900">Compagnies</p>
                    <label v-for="a in airlineStats" :key="a.code" class="flex cursor-pointer items-start gap-3 py-1.5">
                        <input type="checkbox" class="mt-0.5 h-5 w-5 rounded border-navy-300 text-sky-600 focus:ring-sky-500" :checked="!f.noAirline.includes(a.code)" @change="toggle(f.noAirline, a.code)" />
                        <span>
                            <span class="block text-navy-900">{{ a.name }}</span>
                            <span class="text-xs text-navy-500">{{ a.from != null ? `a partir de ${fmt(a.from)}` : '—' }}</span>
                        </span>
                    </label>
                </section>

                <p class="rounded-xl bg-white p-4 text-xs text-navy-600">
                    Les prix sont revalides aupres de la compagnie avant le paiement. Votre billet electronique est envoye uniquement apres son emission.
                </p>
            </aside>

            <!-- Resultats -->
            <div class="min-w-0 space-y-3">
                <div class="grid grid-cols-2 gap-2 lg:hidden">
                    <button type="button" class="rounded-lg bg-white py-2.5 text-sm font-bold text-navy-900 shadow-sm" @click="showFilters = !showFilters">Filtrer{{ activeFilters ? ' •' : '' }}</button>
                    <select v-model="f.sort" class="rounded-lg border-0 bg-white py-2.5 text-center text-sm font-bold text-navy-900 shadow-sm">
                        <option value="best">Les meilleurs</option>
                        <option value="price">Les moins chers</option>
                        <option value="duration">Les plus rapides</option>
                        <option value="departure">Heure de depart</option>
                    </select>
                </div>

                <div class="flex items-end justify-between gap-2">
                    <p class="text-sm text-navy-700"><strong class="text-navy-900">{{ filtered.length }} resultat{{ filtered.length > 1 ? 's' : '' }}</strong> tries par {{ sortLabel }}<span v-if="offers.length > filtered.length" class="text-navy-500"> ({{ offers.length - filtered.length }} masque{{ offers.length - filtered.length > 1 ? 's' : '' }} par les filtres)</span></p>
                </div>
                <div v-if="searching" class="h-1 overflow-hidden rounded-full bg-navy-100"><div class="h-full w-1/3 animate-[mk-progress_1.4s_ease-in-out_infinite] rounded-full bg-sky-600" /></div>

                <!-- Onglets -->
                <div v-if="filtered.length" class="grid grid-cols-3 overflow-hidden rounded-xl bg-white shadow-sm sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="border-r px-3 py-3 text-left transition last:border-r-0"
                        :class="f.sort === t.key ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-50'"
                        @click="f.sort = t.key"
                    >
                        <p class="truncate text-xs sm:text-sm">{{ t.label }}</p>
                        <p class="whitespace-nowrap text-sm font-extrabold sm:text-lg">{{ t.offer ? fmt(t.offer.total_amount) : '—' }}</p>
                        <p class="truncate text-[11px]" :class="f.sort === t.key ? 'text-white/75' : 'text-navy-500'">{{ duration(t.avg) }} en moyenne</p>
                    </button>
                    <label class="hidden items-center gap-1 px-4 text-sm font-bold text-navy-900 sm:flex">
                        Trier
                        <select v-model="f.sort" class="border-0 bg-transparent py-0 pr-7 text-sm font-bold focus:ring-0">
                            <option value="best">Meilleurs</option>
                            <option value="price">Prix</option>
                            <option value="duration">Duree</option>
                            <option value="departure">Depart</option>
                        </select>
                    </label>
                </div>

                <div v-if="expired" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <span>Ces prix datent de plus de 30 minutes et peuvent avoir change.</span>
                    <button type="button" class="font-semibold underline" @click="research()">Actualiser les prix</button>
                </div>
                <div v-if="supplierErrors" class="rounded-xl border border-gray-200 bg-white p-3 text-xs text-navy-600">
                    Certains fournisseurs n'ont pas repondu : les resultats peuvent etre incomplets.
                </div>

                <!-- Aucun vol direct, mais des vols avec escales -->
                <div v-if="!filtered.length && offers.length" class="flex gap-3 rounded-xl bg-white p-4 text-sm text-navy-800 shadow-sm">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-white">i</span>
                    <div>
                        <p v-if="f.stops.length === 1 && f.stops[0] === 'direct'">Nous n'avons malheureusement trouve aucun vol direct pour ces dates, mais nous avons identifie {{ offers.length }} vol{{ offers.length > 1 ? 's' : '' }} avec escales.</p>
                        <p v-else>Aucun vol ne correspond a ces filtres ({{ offers.length }} vol{{ offers.length > 1 ? 's' : '' }} disponible{{ offers.length > 1 ? 's' : '' }} au total).</p>
                        <button type="button" class="mt-2 font-bold text-navy-900 underline" @click="resetFilters">Voir tous les vols</button>
                    </div>
                </div>

                <FlightOfferCard
                    v-for="o in sorted.slice(0, limit)"
                    :key="o.uuid"
                    :offer="o"
                    :airports="airports"
                    :badges="badgesFor(o)"
                    :favorite="favorites.includes(o.uuid)"
                    @favorite="toggleFav"
                />
                <button v-if="sorted.length > limit" type="button" class="w-full rounded-xl bg-white py-3 text-sm font-bold text-navy-900 shadow-sm hover:bg-navy-50" @click="limit += 15">
                    Afficher plus de resultats ({{ sorted.length - limit }})
                </button>

                <div v-if="!offers.length" class="rounded-xl bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-semibold text-navy-900">Aucun vol disponible pour ces criteres</p>
                    <p class="mt-2 text-sm text-navy-600">
                        Essayez d'autres dates (fleches en haut), une autre classe ou un aeroport voisin. Notre equipe peut aussi vous trouver un billet :
                        <a href="/#contact" class="font-semibold text-gold-600 hover:underline">contactez la billetterie</a>.
                    </p>
                    <button type="button" class="btn-gold mt-4" @click="showForm = true">Modifier la recherche</button>
                </div>
            </div>
        </div>
    </div>
</template>
