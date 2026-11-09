<script setup>
// Resultats de recherche de vols (presentation inspiree de Skyscanner) :
// bandeau trajet + dates, onglets Les meilleurs / Les moins chers / Les plus
// rapides, filtres (escales, compagnies) et cartes de vols.
import MainLayout from '@/Layouts/MainLayout.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import FlightOfferCard from '@/Components/FlightOfferCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { duration, money, offerDuration, routeLabel, shortDate } from '@/utils/flights';

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

const showForm = ref(false);
const showFilters = ref(false);
const loading = ref(false);
const pick = (k, d) => (typeof props.filters?.[k] === 'string' ? props.filters[k] : d);
const f = reactive({ stops: pick('stops', ''), airline: pick('airline', ''), sort: pick('sort', 'best') });

const apply = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null));
    router.get(`/voyage/vols/resultats/${props.search.uuid}`, query, {
        preserveState: true, preserveScroll: true, replace: true,
        onStart: () => (loading.value = true), onFinish: () => (loading.value = false),
    });
};
const setSort = (v) => { f.sort = v; apply(); };
const showAllStops = () => { f.stops = ''; apply(); };

const city = (code) => props.airports?.[code]?.city ?? code;
const labels = computed(() => ({
    origin: props.airports?.[props.search.origin] ? `${city(props.search.origin)} (${props.search.origin})` : props.search.origin,
    destination: props.airports?.[props.search.destination] ? `${city(props.search.destination)} (${props.search.destination})` : props.search.destination,
}));
const isMulti = computed(() => props.search.trip_type === 'multicity' && (props.search.legs ?? []).length >= 2);
const title = computed(() => (isMulti.value
    ? routeLabel(props.search.legs, city)
    : `${city(props.search.origin)} - ${city(props.search.destination)}`));
const datesText = computed(() => {
    if (isMulti.value) {
        const l = props.search.legs;
        return `${shortDate(l[0].date)} - ${shortDate(l[l.length - 1].date)}`;
    }
    return props.search.return_date ? `${shortDate(props.search.depart_date)} - ${shortDate(props.search.return_date)}` : shortDate(props.search.depart_date);
});
const paxText = computed(() => {
    const n = props.search.adults + props.search.children + props.search.infants;
    return `${n} voyageur${n > 1 ? 's' : ''}`;
});
const tripText = computed(() => ({ roundtrip: 'Aller-retour', oneway: 'Aller simple', multicity: 'Multi-destinations' }[props.search.trip_type] ?? ''));

// Onglets facon Skyscanner : meilleur prix et duree de chaque tri.
const cheapest = computed(() => props.offers.reduce((m, o) => (!m || Number(o.total_amount) < Number(m.total_amount) ? o : m), null));
const fastest = computed(() => props.offers.reduce((m, o) => (!m || offerDuration(o) < offerDuration(m) ? o : m), null));
const tabs = computed(() => [
    { key: 'best', label: 'Les meilleurs', offer: props.offers[0] && f.sort === 'best' ? props.offers[0] : null },
    { key: 'price', label: 'Les moins chers', offer: cheapest.value },
    { key: 'duration', label: 'Les plus rapides', offer: fastest.value },
]);
const sortLabel = computed(() => tabs.value.find((t) => t.key === f.sort)?.label ?? 'Heure de depart');
const badgesFor = (o) => [
    o === cheapest.value && props.offers.length > 1 ? 'Le moins cher' : null,
    o === fastest.value && props.offers.length > 1 ? 'Le plus rapide' : null,
].filter(Boolean);

const directOnly = computed(() => f.stops === '0');
const hiddenByFilters = computed(() => Math.max(0, props.totalCount - props.offers.length));

const refresh = () => router.get('/voyage/vols/recherche', {
    trip_type: props.search.trip_type,
    ...(isMulti.value
        ? { legs: props.search.legs }
        : {
              origin: props.search.origin, destination: props.search.destination,
              depart_date: props.search.depart_date?.slice(0, 10), return_date: props.search.return_date?.slice(0, 10) ?? undefined,
          }),
    adults: props.search.adults, children: props.search.children, infants: props.search.infants, cabin: props.search.cabin,
    ...(directOnly.value ? { direct_only: 1 } : {}),
});
</script>

<template>
    <Head :title="`Vols ${title}`" />

    <!-- Bandeau trajet -->
    <section class="sticky top-0 z-20 bg-navy-900 px-4 py-3 text-white shadow sm:px-6">
        <div class="mx-auto flex max-w-6xl items-center gap-3">
            <Link href="/voyage?type=vol" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full hover:bg-white/10" title="Retour">←</Link>
            <button type="button" class="min-w-0 flex-1 rounded-xl bg-white/10 px-4 py-2 text-left hover:bg-white/15" @click="showForm = !showForm">
                <p class="truncate font-bold">{{ title }}</p>
                <p class="truncate text-xs text-white/75">{{ datesText }} · {{ paxText }} · {{ tripText }} · {{ cabins[search.cabin] ?? search.cabin }}</p>
            </button>
            <button type="button" class="hidden rounded-full border border-white/40 px-4 py-2 text-sm font-semibold hover:bg-white/10 sm:block" @click="showForm = !showForm">
                {{ showForm ? 'Fermer' : 'Modifier' }}
            </button>
        </div>
        <div v-if="showForm" class="mx-auto mt-3 max-w-6xl rounded-2xl bg-white p-4 text-navy-900">
            <FlightSearchForm :initial="{ ...search, direct_only: directOnly }" :labels="labels" :cabins="cabins" :airports="airports" />
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <!-- Filtrer / Trier (mobile) -->
        <div class="mb-4 grid grid-cols-2 gap-3 lg:hidden">
            <button type="button" class="rounded-xl bg-navy-50 py-2.5 text-sm font-bold text-navy-900" @click="showFilters = !showFilters">Filtrer</button>
            <select v-model="f.sort" class="rounded-xl border-0 bg-navy-50 py-2.5 text-center text-sm font-bold text-navy-900" @change="apply">
                <option value="best">Les meilleurs</option>
                <option value="price">Les moins chers</option>
                <option value="duration">Les plus rapides</option>
                <option value="departure">Heure de depart</option>
            </select>
        </div>

        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-6 text-sm" :class="showFilters ? 'block' : 'hidden lg:block'">
                <div class="rounded-2xl border p-4">
                    <p class="mb-2 font-bold text-navy-900">Escales</p>
                    <label v-for="o in [{ v: '', l: 'Toutes' }, { v: '0', l: 'Direct uniquement' }, { v: '1', l: '1 escale max' }, { v: '2', l: '2 escales max' }]" :key="o.v" class="flex items-center gap-2 py-1">
                        <input v-model="f.stops" type="radio" :value="o.v" class="text-navy-900 focus:ring-gold-600" @change="apply" /> {{ o.l }}
                    </label>
                </div>
                <div v-if="airlines.length > 1" class="rounded-2xl border p-4">
                    <p class="mb-2 font-bold text-navy-900">Compagnies</p>
                    <label class="flex items-center gap-2 py-1"><input v-model="f.airline" type="radio" value="" class="text-navy-900 focus:ring-gold-600" @change="apply" /> Toutes</label>
                    <label v-for="a in airlines" :key="a.code" class="flex items-center gap-2 py-1">
                        <input v-model="f.airline" type="radio" :value="a.code" class="text-navy-900 focus:ring-gold-600" @change="apply" /> {{ a.name }}
                    </label>
                </div>
                <p class="rounded-2xl bg-navy-50/60 p-4 text-xs text-navy-600">
                    Les prix sont revalides aupres de la compagnie avant le paiement. Votre billet electronique est envoye uniquement apres son emission.
                </p>
            </aside>

            <div class="space-y-4">
                <!-- Onglets de tri -->
                <div v-if="offers.length" class="grid grid-cols-3 gap-2">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="rounded-2xl border px-3 py-2.5 text-left transition"
                        :class="f.sort === t.key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 bg-white text-navy-900 hover:border-navy-300'"
                        @click="setSort(t.key)"
                    >
                        <p class="truncate text-[11px] font-bold sm:text-sm">{{ t.label }}</p>
                        <p v-if="t.offer" class="mt-0.5 whitespace-nowrap text-[13px] font-extrabold sm:text-base">{{ money(t.offer.total_amount, t.offer.currency) }}</p>
                        <p v-if="t.offer" class="text-[11px]" :class="f.sort === t.key ? 'text-white/75' : 'text-navy-500'">{{ duration(offerDuration(t.offer)) }} (total)</p>
                    </button>
                </div>

                <div v-if="expired" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <span>Ces prix datent de plus de 30 minutes et peuvent avoir change.</span>
                    <button type="button" class="font-semibold underline" @click="refresh">Actualiser les prix</button>
                </div>
                <div v-if="supplierErrors" class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-navy-600">
                    Certains fournisseurs n'ont pas repondu : les resultats peuvent etre incomplets.
                </div>

                <!-- Aucun vol direct mais des vols avec escales -->
                <div v-if="directOnly && !offers.length && hiddenByFilters" class="flex gap-3 rounded-2xl bg-navy-50 p-4 text-sm text-navy-800">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-white">i</span>
                    <div>
                        <p>Nous n'avons malheureusement trouve aucun vol direct pour ces dates, mais nous avons identifie {{ hiddenByFilters }} vol{{ hiddenByFilters > 1 ? 's' : '' }} avec escales.</p>
                        <button type="button" class="mt-2 font-bold text-navy-900 underline" @click="showAllStops">Voir les vols avec escales</button>
                    </div>
                </div>

                <p v-if="offers.length" class="text-sm text-navy-600">
                    <strong class="text-navy-900">{{ offers.length }} resultat{{ offers.length > 1 ? 's' : '' }}</strong> tries par {{ sortLabel }}
                    <span v-if="hiddenByFilters"> · {{ hiddenByFilters }} masque{{ hiddenByFilters > 1 ? 's' : '' }} par les filtres</span>
                </p>

                <template v-if="loading">
                    <div v-for="n in 3" :key="n" class="animate-pulse rounded-2xl border p-5">
                        <div v-for="m in 2" :key="m" class="flex items-center gap-4 py-2">
                            <div class="h-10 w-10 rounded-lg bg-navy-100" />
                            <div class="flex-1 space-y-2"><div class="h-3 w-1/3 rounded bg-navy-100" /><div class="h-3 w-1/5 rounded bg-navy-50" /></div>
                            <div class="space-y-2"><div class="h-3 w-16 rounded bg-navy-100" /><div class="h-3 w-12 rounded bg-navy-50" /></div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <FlightOfferCard v-for="o in offers" :key="o.uuid" :offer="o" :airports="airports" :badges="badgesFor(o)" />
                </template>

                <div v-if="!offers.length && !(directOnly && hiddenByFilters)" class="rounded-2xl border p-10 text-center">
                    <p class="text-lg font-semibold text-navy-900">Aucun vol disponible pour ces criteres</p>
                    <p class="mt-2 text-sm text-navy-600">
                        Essayez d'autres dates, une autre classe ou un aeroport voisin. Notre equipe peut aussi vous trouver un billet :
                        <a href="/#contact" class="font-semibold text-gold-600 hover:underline">contactez la billetterie</a>.
                    </p>
                    <button type="button" class="btn-gold mt-4" @click="showForm = true">Modifier la recherche</button>
                </div>
            </div>
        </div>
    </div>
</template>
