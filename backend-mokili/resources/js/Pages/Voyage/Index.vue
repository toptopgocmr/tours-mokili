<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import RideCard from '@/Components/RideCard.vue';
import RideSearchBar from '@/Components/RideSearchBar.vue';
import { dayHeading, RIDE_MODES, RIDE_PAGES, rideHour, rideMinutes, TIME_SLOTS } from '@/utils/rides';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offers: { type: Object, required: true },
    returnOffers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    transportModes: { type: Object, default: () => ({}) },
    cabins: { type: Object, default: () => ({}) },
    popularAirports: { type: Array, default: () => [] },
    tripScopes: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
});

const destination = ref(props.filters.destination ?? '');
const type = ref(props.filters.type ?? '');
const mode = ref(props.filters.mode ?? '');
const scope = ref(props.filters.scope ?? '');
const departement = ref(props.filters.departement ?? '');
// Trajets (Circuits) : depart, date, passagers.
const from = ref(props.filters.from ?? '');
const date = ref(props.filters.date ?? '');
const passengers = ref(Number(props.filters.passengers ?? 1) || 1);
const time = ref(props.filters.time ?? '');
const returnDate = ref(props.filters.return_date ?? '');
const today = new Date().toISOString().slice(0, 10);
const isRides = computed(() => type.value === 'circuit');
const swapCities = () => { [from.value, destination.value] = [destination.value, from.value]; };

// Page du moyen choisi (titre, accroche, trajets populaires).
const ridePage = computed(() => RIDE_PAGES[mode.value] ?? RIDE_PAGES['']);

// Filtres et tri facon BlaBlaCar (sur les trajets affiches).
const rideSort = ref('departure');
const showFilters = ref(false);
const rideSlots = ref([]);
const rideOperators = ref([]);
const toggleIn = (list, v) => { const i = list.indexOf(v); i >= 0 ? list.splice(i, 1) : list.push(v); };
const operators = computed(() => [...new Set(props.offers.data.map((o) => o.airline).filter(Boolean))].sort());
const slotCount = (slot) => props.offers.data.filter((o) => slot.test(rideHour(o.departure_at))).length;
const filteredRides = computed(() => {
    const list = props.offers.data.filter((o) =>
        (!rideSlots.value.length || TIME_SLOTS.some((t) => rideSlots.value.includes(t.key) && t.test(rideHour(o.departure_at))))
        && (!rideOperators.value.length || rideOperators.value.includes(o.airline)));
    const price = (o) => Number(o.discounted_price ?? o.price);
    if (rideSort.value === 'price') return [...list].sort((a, b) => price(a) - price(b));
    if (rideSort.value === 'duration') return [...list].sort((a, b) => rideMinutes(a) - rideMinutes(b));
    return list;
});
const cheapestId = computed(() => (filteredRides.value.length > 1 ? [...filteredRides.value].sort((a, b) => Number(a.discounted_price ?? a.price) - Number(b.discounted_price ?? b.price))[0].id : null));
const fastestId = computed(() => {
    const l = filteredRides.value.filter((o) => Number.isFinite(rideMinutes(o)));
    return l.length > 1 ? [...l].sort((a, b) => rideMinutes(a) - rideMinutes(b))[0].id : null;
});
const tomorrowIso = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
const cheapest = computed(() => (props.offers.data.length ? Math.min(...props.offers.data.map((o) => Number(o.discounted_price ?? o.price))) : null));

// Bandeau des 7 prochains jours.
const nextDays = computed(() => Array.from({ length: 7 }, (_, i) => {
    const d = new Date();
    d.setDate(d.getDate() + i);
    const iso = d.toISOString().slice(0, 10);
    return { iso, label: i === 0 ? "Aujourd'hui" : i === 1 ? 'Demain' : d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) };
}));
const pickDate = (iso) => { date.value = date.value === iso ? '' : iso; search(); };
const pickRoute = ([a, b]) => { from.value = a; destination.value = b; search(); };

// Trajets regroupes par jour (comme BlaBlaCar).
const rideDays = computed(() => {
    const groups = [];
    for (const o of filteredRides.value) {
        const key = o.departure_at ? new Date(o.departure_at).toISOString().slice(0, 10) : 'tbd';
        let g = groups.find((x) => x.key === key);
        if (!g) groups.push((g = { key, label: dayHeading(o.departure_at), items: [] }));
        g.items.push(o);
    }
    return groups;
});

// Icones des moyens de transport des circuits.
const MODE_ICONS = { avion: '✈️', bus: '🚌', bateau: '⛴️', canot: '🚤', train: '🚆', taxi: '🚕', voiture: '🚙', combine: '🧭' };
const modeChips = computed(() => [{ value: '', label: 'Tous les transports', icon: '' },
    ...Object.entries(props.transportModes).map(([value, label]) => ({ value, label, icon: MODE_ICONS[value] ?? '' }))]);

// Partenaires transport affiches selon le moyen choisi.
const partnersSubtitle = computed(() => ({
    bus: 'Compagnies de bus interurbains : Brazzaville, Pointe-Noire, Dolisie, Oyo, Ouesso...',
    bateau: 'Bateaux sur le fleuve Congo et ses affluents.',
    canot: 'Canots rapides et pirogues (Brazzaville - Kinshasa, fleuve Congo).',
    train: 'Ligne ferroviaire Congo-Ocean : Brazzaville - Dolisie - Pointe-Noire.',
    taxi: 'Taxis interurbains et VTC entre les villes du Congo.',
}[mode.value] ?? 'Compagnies aeriennes, bus interurbains, train et canots rapides vers Kinshasa.'));

const types = [
    { value: '', label: 'Tous' },
    { value: 'vol', label: 'Vols' },
    { value: 'circuit', label: 'Circuits' },
];

const search = () => {
    const query = Object.fromEntries(
        Object.entries({
            destination: destination.value,
            type: type.value,
            mode: type.value === 'circuit' ? mode.value : '',
            scope: type.value === 'circuit' ? scope.value : '',
            departement: type.value === 'circuit' ? departement.value : '',
            from: type.value === 'circuit' ? from.value : '',
            date: type.value === 'circuit' ? date.value : '',
            time: type.value === 'circuit' && date.value ? time.value : '',
            return_date: type.value === 'circuit' ? returnDate.value : '',
            passengers: type.value === 'circuit' && passengers.value > 1 ? passengers.value : '',
        }).filter(([, v]) => v)
    );
    router.get('/voyage', query, { preserveState: true, preserveScroll: true, replace: true });
};

const setType = (value) => {
    type.value = value;
    if (value !== 'circuit') {
        mode.value = '';
        scope.value = '';
        departement.value = '';
    }
    search();
};

const setMode = (value) => {
    mode.value = value;
    search();
};

const setScope = (value) => {
    scope.value = scope.value === value ? '' : value;
    search();
};

const isFlight = (offer) => offer.type === 'vol';
const cardTitle = (offer) => {
    if (!isFlight(offer)) return offer.title;
    const from = offer.origin_city || 'Brazzaville';
    return from === offer.destination_city ? `Vol vers ${offer.destination_city}` : `${from} → ${offer.destination_city}`;
};
const discounted = (offer) => (Number(offer.discount_percent) > 0 ? Math.round(Number(offer.discounted_price)) : null);

const cardSubtitle = (offer) => {
    if (isFlight(offer)) {
        return [offer.airline, offer.return_at ? 'Aller-retour' : 'Aller simple'].filter(Boolean).join(' · ');
    }
    if (offer.type === 'circuit' && offer.transport_mode_label) {
        return [offer.transport_mode_label, offer.trip_scope_label, offer.airline].filter(Boolean).join(' - ');
    }
    return [offer.type, offer.airline].filter(Boolean).join(' - ');
};
</script>

<template>
    <Head title="Voyage" />

    <!-- Hero + search: style Booking/Kayak (recherche flottante par
         dessus la photo, onglets de type comme leurs onglets Vols/
         Hebergements/Voitures). -->
    <!-- TRAJETS : accueil facon SNCF Connect (fond clair, titre centre). -->
    <section v-if="isRides" class="bg-[#f3f6fb] px-4 pb-10 pt-6 sm:px-6">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-wrap justify-center gap-2 whitespace-nowrap text-sm">
                <button type="button" class="rounded-full px-4 py-1.5 font-semibold text-navy-700 hover:bg-white" @click="setType('vol')">✈ Vols</button>
                <span class="rounded-full bg-navy-900 px-4 py-1.5 font-semibold text-white">Trains, bus et canots</span>
                <button type="button" class="rounded-full px-4 py-1.5 font-semibold text-navy-700 hover:bg-white" @click="setType('')">Toutes les offres</button>
            </div>
            <h1 class="text-center text-2xl font-extrabold text-navy-900 sm:text-4xl">Trains, bus, canots : trouvez facilement votre trajet</h1>
            <div class="mt-6">
                <RideSearchBar
                    v-model:mode="mode"
                    v-model:scope="scope"
                    v-model:from="from"
                    v-model:to="destination"
                    v-model:date="date"
                    v-model:time="time"
                    v-model:return-date="returnDate"
                    v-model:passengers="passengers"
                    @search="search"
                />
            </div>
        </div>
    </section>

    <template v-else>
    <section class="relative h-[260px] overflow-hidden sm:h-[300px]">
        <img src="/images/hero.jpg" alt="Voyage" class="h-full w-full object-cover object-[center_20%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-14 text-left sm:px-10 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Voyage</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">Ou voulez-vous partir ?</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">
                Billets d'avion et trajets en bus, train ou taxi.
            </p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-3">
                <button
                    v-for="t in types"
                    :key="t.value"
                    type="button"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="type === t.value ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                    @click="setType(t.value)"
                >
                    {{ t.label }}
                </button>
            </div>
            <!-- VOLS : moteur de billetterie aerienne -->
            <div v-if="type === 'vol'" class="mt-3">
                <FlightSearchForm :cabins="cabins" :suggestions="popularAirports" />
                <Link href="/voyage/vols/horaires" class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-2 text-sm font-semibold text-navy-900 shadow hover:bg-gold-50">
                    🕓 Programme des vols : departs et arrivees en direct →
                </Link>
            </div>
            <!-- CIRCUITS : recherche de trajet facon BlaBlaCar -->
            <form v-else-if="isRides" class="mt-3 space-y-3" @submit.prevent="search">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="m in RIDE_MODES"
                        :key="m.value"
                        type="button"
                        class="flex items-center gap-1.5 rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                        :class="mode === m.value ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700 hover:border-navy-400'"
                        @click="setMode(m.value)"
                    >
                        <span>{{ m.icon }}</span>{{ m.label }}
                    </button>
                </div>
                <div class="grid overflow-hidden rounded-2xl border border-gray-200 sm:grid-cols-[1fr_auto_1fr_170px_150px_auto]">
                    <label class="flex items-center gap-2 border-b px-4 py-2 sm:border-b-0 sm:border-r">
                        <span class="h-3 w-3 shrink-0 rounded-full border-2 border-navy-900" />
                        <input v-model="from" type="text" placeholder="Depart (ex : Brazzaville)" class="w-full border-0 p-1 text-sm font-semibold text-navy-900 focus:ring-0" />
                    </label>
                    <button type="button" class="hidden items-center px-2 text-navy-500 hover:text-navy-900 sm:flex" title="Inverser" @click="swapCities">⇄</button>
                    <label class="flex items-center gap-2 border-b px-4 py-2 sm:border-b-0 sm:border-r">
                        <span class="h-3 w-3 shrink-0 rounded-full border-2 border-navy-900" />
                        <input v-model="destination" type="text" placeholder="Destination (ex : Pointe-Noire)" class="w-full border-0 p-1 text-sm font-semibold text-navy-900 focus:ring-0" />
                    </label>
                    <label class="flex items-center gap-2 border-b px-3 py-2 sm:border-b-0 sm:border-r">
                        <span>📅</span>
                        <input v-model="date" type="date" :min="today" class="w-full border-0 p-1 text-sm font-semibold text-navy-900 focus:ring-0" />
                    </label>
                    <label class="flex items-center gap-2 border-b px-3 py-2 sm:border-b-0 sm:border-r">
                        <span>👤</span>
                        <select v-model.number="passengers" class="w-full border-0 p-1 text-sm font-semibold text-navy-900 focus:ring-0">
                            <option v-for="n in 8" :key="n" :value="n">{{ n }} passager{{ n > 1 ? 's' : '' }}</option>
                        </select>
                    </label>
                    <button type="submit" class="bg-sky-500 px-8 py-3 text-sm font-bold text-white hover:bg-sky-600">Rechercher</button>
                </div>
            </form>
            <form v-else class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="search">
                <input
                    v-model="destination"
                    type="text"
                    placeholder="Destination (ex: Paris, Dubai...)"
                    class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600"
                />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>
    </template>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <div v-if="type === 'vol'" class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-xl font-bold text-navy-900">Offres vols du moment</h2>
                <p class="text-sm text-navy-500">Ou lancez une recherche ci-dessus pour comparer les vols et reserver votre billet.</p>
            </div>
            <Link href="/voyage/vols/mes-billets" class="text-sm font-semibold text-gold-600 hover:underline">Mes billets d'avion →</Link>
        </div>

        <!-- CIRCUITS : trajets du jour, facon BlaBlaCar -->
        <div v-if="isRides" class="mx-auto max-w-6xl">
            <!-- En-tete du moyen de transport -->
            <div class="mb-5">
                <h2 class="text-2xl font-extrabold text-navy-900 sm:text-3xl">{{ ridePage.title }}</h2>
                <p class="mt-1 text-navy-600">{{ ridePage.subtitle }}</p>
            </div>

            <!-- 7 prochains jours -->
            <div class="mb-6 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="d in nextDays"
                    :key="d.iso"
                    type="button"
                    class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition"
                    :class="date === d.iso ? 'border-sky-500 bg-sky-500 text-white' : 'border-gray-200 bg-white text-navy-800 hover:border-sky-400'"
                    @click="pickDate(d.iso)"
                >
                    {{ d.label }}
                </button>
            </div>

            <div v-if="offers.data.length" class="grid gap-8 lg:grid-cols-[260px_1fr]">
                <!-- Filtres -->
                <button type="button" class="flex items-center justify-center gap-2 rounded-full border-2 border-sky-700 py-2 text-sm font-semibold text-sky-700 lg:hidden" @click="showFilters = !showFilters">
                    ⚙ {{ showFilters ? 'Masquer les filtres' : 'Filtrer et trier' }}<span v-if="rideSlots.length + rideOperators.length" class="rounded-full bg-sky-700 px-1.5 text-xs text-white">{{ rideSlots.length + rideOperators.length }}</span>
                </button>
                <aside class="space-y-6 text-sm lg:block" :class="showFilters ? 'block' : 'hidden'">
                    <div>
                        <p class="mb-2 text-base font-extrabold text-navy-900">Trier par</p>
                        <label v-for="o in [['departure', 'Depart le plus tot', '🕒'], ['price', 'Prix le plus bas', '💰'], ['duration', 'Trajet le plus court', '⏱️']]" :key="o[0]" class="flex cursor-pointer items-center justify-between rounded-lg px-2 py-2 hover:bg-navy-50">
                            <span class="flex items-center gap-2 font-medium text-navy-900"><span>{{ o[2] }}</span>{{ o[1] }}</span>
                            <input v-model="rideSort" type="radio" :value="o[0]" class="text-sky-500 focus:ring-sky-500" />
                        </label>
                    </div>
                    <div class="border-t pt-5">
                        <p class="mb-2 text-base font-extrabold text-navy-900">Heure de depart</p>
                        <label v-for="t in TIME_SLOTS" :key="t.key" class="flex cursor-pointer items-center justify-between rounded-lg px-2 py-2 hover:bg-navy-50" :class="!slotCount(t) && 'opacity-40'">
                            <span class="font-medium text-navy-900">{{ t.label }}</span>
                            <span class="flex items-center gap-2 text-xs text-navy-500">{{ slotCount(t) }}
                                <input type="checkbox" class="rounded text-sky-500 focus:ring-sky-500" :checked="rideSlots.includes(t.key)" :disabled="!slotCount(t)" @change="toggleIn(rideSlots, t.key)" />
                            </span>
                        </label>
                    </div>
                    <div v-if="operators.length > 1" class="border-t pt-5">
                        <p class="mb-2 text-base font-extrabold text-navy-900">Transporteurs</p>
                        <label v-for="op in operators" :key="op" class="flex cursor-pointer items-center justify-between rounded-lg px-2 py-2 hover:bg-navy-50">
                            <span class="font-medium text-navy-900">{{ op }}</span>
                            <input type="checkbox" class="rounded text-sky-500 focus:ring-sky-500" :checked="rideOperators.includes(op)" @change="toggleIn(rideOperators, op)" />
                        </label>
                    </div>
                    <button v-if="rideSlots.length || rideOperators.length" type="button" class="text-sm font-semibold text-sky-600 hover:underline" @click="rideSlots = []; rideOperators = []">Effacer les filtres</button>
                </aside>

                <!-- Trajets -->
                <div>
                    <div v-if="from || destination" class="mb-3 rounded-2xl bg-navy-900 px-5 py-3 text-white">
                        <p class="text-xs uppercase tracking-wide text-gold-400">Aller</p>
                        <p class="font-bold">{{ from || 'Partout' }} → {{ destination || 'Partout' }}<span class="font-normal text-navy-200"> · {{ date ? dayHeading(`${date}T12:00:00Z`) : 'Toutes les dates' }}{{ time !== '' && date ? ` · des ${time}h` : '' }} · {{ passengers }} voyageur{{ passengers > 1 ? 's' : '' }}</span></p>
                    </div>
                    <p class="mb-4 text-sm text-navy-600">
                        <strong class="text-navy-900">{{ filteredRides.length }} trajet{{ filteredRides.length > 1 ? 's' : '' }} disponible{{ filteredRides.length > 1 ? 's' : '' }}</strong>
                        <span v-if="from || destination"> · {{ from || 'Partout' }} → {{ destination || 'Partout' }}</span>
                        <span v-if="cheapest != null"> · a partir de {{ cheapest.toLocaleString('fr-FR') }} XAF</span>
                    </p>
                    <section v-for="g in rideDays" :key="g.key" class="mb-6">
                        <h3 class="mb-3 text-lg font-extrabold text-navy-900">{{ g.label }}</h3>
                        <div class="space-y-3">
                            <RideCard v-for="o in g.items" :key="o.id" :offer="o" :passengers="passengers" :cheapest="o.id === cheapestId" :fastest="o.id === fastestId && o.id !== cheapestId" />
                        </div>
                    </section>
                    <p v-if="!filteredRides.length" class="rounded-2xl bg-navy-50/60 p-6 text-center text-navy-700">Aucun trajet ne correspond a ces filtres.</p>
                    <!-- Retour (facon SNCF Connect) -->
                    <section v-if="returnDate" class="mt-8 border-t pt-6">
                        <h3 class="text-lg font-extrabold text-navy-900">↩ Retour · {{ dayHeading(`${returnDate}T12:00:00Z`) }}</h3>
                        <p class="mb-3 text-sm text-navy-600">{{ destination || 'Partout' }} → {{ from || 'Partout' }}</p>
                        <div v-if="returnOffers.length" class="space-y-3">
                            <RideCard v-for="o in returnOffers" :key="`r${o.id}`" :offer="o" :passengers="passengers" />
                        </div>
                        <p v-else class="rounded-2xl bg-navy-50/60 p-6 text-center text-navy-700">Aucun trajet retour ce jour-la. Essayez une autre date de retour.</p>
                    </section>
                    <div v-if="offers.next_page_url" class="text-center">
                        <Link :href="offers.next_page_url" preserve-scroll class="inline-block rounded-full border border-navy-200 px-6 py-2 text-sm font-semibold text-navy-900 hover:bg-navy-50">Plus de trajets</Link>
                    </div>
                </div>
            </div>

            <!-- Aucun trajet : message + idees -->
            <div v-else class="rounded-2xl border border-navy-100 bg-white p-8 text-center">
                <p class="text-4xl">{{ RIDE_MODES.find((m) => m.value === mode)?.icon ?? '🧭' }}</p>
                <p class="mt-2 text-xl font-bold text-navy-900">Aucun trajet disponible pour cette recherche</p>
                <p class="mx-auto mt-1 max-w-xl text-navy-600">Nos transporteurs ajoutent de nouveaux departs chaque jour. Essayez une autre date, un autre moyen de transport ou l'un des trajets populaires ci-dessous.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <button v-if="date !== tomorrowIso" type="button" class="rounded-full border-2 border-sky-700 px-5 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-700 hover:text-white" @click="pickDate(tomorrowIso)">Voir demain</button>
                    <button v-if="date" type="button" class="rounded-full border-2 border-sky-700 px-5 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-700 hover:text-white" @click="date = ''; time = ''; search()">Toutes les dates</button>
                    <button v-if="mode || scope" type="button" class="rounded-full border-2 border-sky-700 px-5 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-700 hover:text-white" @click="mode = ''; scope = ''; search()">Tous les moyens de transport</button>
                    <a href="/#contact" class="rounded-full bg-navy-900 px-5 py-2 text-sm font-semibold text-white hover:bg-navy-800">Demander un trajet</a>
                </div>
            </div>

            <!-- Trajets populaires -->
            <div class="mt-10">
                <h3 class="mb-4 text-xl font-extrabold text-navy-900">Trajets populaires</h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="r in ridePage.routes"
                        :key="r.join('-')"
                        type="button"
                        class="flex items-center justify-between rounded-2xl bg-white px-5 py-4 text-left shadow-[0_1px_6px_rgba(5,15,33,0.12)] transition hover:shadow-[0_4px_14px_rgba(5,15,33,0.18)]"
                        @click="pickRoute(r)"
                    >
                        <span class="font-bold text-navy-900">{{ r[0] }} <span class="text-sky-500">→</span> {{ r[1] }}</span>
                        <span class="text-xl text-navy-400">›</span>
                    </button>
                </div>
            </div>

            <!-- Comment ca marche -->
            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                <div v-for="(st, i) in [['🔎', 'Cherchez', 'Choisissez le depart, la destination, la date et le nombre de passagers.'], ['🎫', 'Reservez', 'Comparez les horaires et les prix, puis choisissez votre trajet.'], ['📱', 'Payez et partez', 'Payez par Mobile Money : votre place est confirmee aussitot.']]" :key="i" class="rounded-2xl bg-white p-5 shadow-[0_1px_6px_rgba(5,15,33,0.12)]">
                    <p class="text-3xl">{{ st[0] }}</p>
                    <p class="mt-2 font-extrabold text-navy-900">{{ i + 1 }}. {{ st[1] }}</p>
                    <p class="mt-1 text-sm text-navy-600">{{ st[2] }}</p>
                </div>
            </div>
        </div>

        <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="offer in offers.data"
                :key="offer.id"
                :href="`/voyage/${offer.slug}`"
                class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg"
            >
                <div class="relative h-36 overflow-hidden">
                    <DestinationImage :city="offer.destination_city" :image="offer.image_url" class="h-full w-full" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent" />
                    <span class="absolute bottom-2 left-3 font-semibold text-white drop-shadow">{{ offer.destination_city }}</span>
                    <span v-if="isFlight(offer)" class="absolute left-3 top-3 rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase text-navy-900 shadow">✈ Vol</span>
                    <span v-if="discounted(offer)" class="absolute right-3 top-3 rounded-full bg-rose-600 px-2.5 py-1 text-[11px] font-bold text-white shadow">-{{ offer.discount_percent }}%</span>
                </div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ cardTitle(offer) }}</p>
                    <p class="text-xs uppercase text-navy-500">
                        <span v-if="offer.type === 'circuit' && MODE_ICONS[offer.transport_mode]" class="mr-1">{{ MODE_ICONS[offer.transport_mode] }}</span>{{ cardSubtitle(offer) }}
                    </p>
                    <p v-if="offer.duration_label" class="text-xs text-navy-500">{{ offer.duration_label }}</p>
                    <p class="mt-2 text-sm font-semibold text-gold-600">
                        <span v-if="isFlight(offer)" class="mr-1 text-xs font-normal text-navy-500">a partir de</span>
                        <span v-if="discounted(offer)" class="mr-1 text-xs font-normal text-navy-400 line-through">{{ Number(offer.price).toLocaleString('fr-FR') }}</span>
                        {{ Number(discounted(offer) ?? offer.price).toLocaleString('fr-FR') }} {{ offer.currency }}
                    </p>
                </div>
            </Link>
        </div>

        <div v-if="!offers.data.length && !isRides" class="mx-auto mt-6 max-w-xl rounded-2xl bg-navy-50/60 p-8 text-center">
            <p class="text-lg font-bold text-navy-900">{{ isRides ? 'Aucun trajet pour cette recherche' : 'Aucune offre pour le moment.' }}</p>
            <p v-if="isRides" class="mt-1 text-sm text-navy-600">Essayez une autre date ou un autre moyen de transport, ou contactez nos transporteurs partenaires ci-dessous.</p>
        </div>
        <PartnersSection
            v-if="type === 'vol'"
            key="vol"
            module="voyage"
            only="compagnie_aerienne"
            title="Compagnies aeriennes"
            subtitle="Les compagnies partenaires de notre billetterie."
        />
        <PartnersSection
            v-else-if="isRides"
            :key="`rides-${mode}`"
            module="voyage"
            title="Nos transporteurs"
            :subtitle="partnersSubtitle"
            :only="{ bus: 'transport_routier', canot: 'canot_rapide', train: 'transport_ferroviaire', taxi: 'taxi_vtc' }[mode] ?? 'transport_routier,transport_ferroviaire,canot_rapide,taxi_vtc'"
        />
        <PartnersSection v-else :key="mode" module="voyage" title="Compagnies & transport" :subtitle="partnersSubtitle" :category="{ bus: 'transport_routier', bateau: 'canot_rapide', canot: 'canot_rapide', train: 'transport_ferroviaire', taxi: 'taxi_vtc', avion: 'compagnie_aerienne' }[mode] ?? ''" />
    </div>
</template>
