<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    transportModes: { type: Object, default: () => ({}) },
    tripScopes: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    airports: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Onglets : Tous / Vols / Circuits (les sejours ne sont plus proposes).
const type = ref(props.filters.type ?? '');
const tabs = [
    { value: '', label: 'Tous' },
    { value: 'vol', label: 'Vols' },
    { value: 'circuit', label: 'Circuits' },
];

const MODE_ICONS = { avion: '✈️', bus: '🚌', bateau: '⛴️', canot: '🚤', train: '🚆', voiture: '🚙', combine: '🧭' };

// ---------- Circuits : recherche facon covoiturage ----------
const trip = reactive({
    origin: props.filters.origin ?? '',
    destination: props.filters.destination ?? '',
    date: props.filters.date ?? '',
    passagers: Number(props.filters.passagers ?? 1),
});
const mode = ref(props.filters.mode ?? '');
const scope = ref(props.filters.scope ?? '');
const departement = ref(props.filters.departement ?? '');
const sort = ref(props.filters.tri ?? 'heure');
const today = new Date().toISOString().slice(0, 10);

const go = (extra = {}) => {
    const isCircuit = type.value === 'circuit';
    const q = {
        type: type.value,
        origin: trip.origin,
        destination: trip.destination,
        ...(isCircuit ? { date: trip.date, passagers: trip.passagers > 1 ? trip.passagers : '', mode: mode.value, scope: scope.value, departement: departement.value, tri: sort.value === 'prix' ? 'prix' : '' } : {}),
        ...extra,
    };
    router.get('/voyage', Object.fromEntries(Object.entries(q).filter(([, v]) => v !== '' && v !== null && v !== undefined)), { preserveState: true, preserveScroll: true, replace: true });
};

const setType = (value) => {
    type.value = value;
    if (value !== 'circuit') {
        mode.value = '';
        scope.value = '';
        departement.value = '';
    }
    go();
};
const setMode = (v) => { mode.value = mode.value === v ? '' : v; go(); };
const setScope = (v) => { scope.value = scope.value === v ? '' : v; go(); };
const setSort = (v) => { sort.value = v; go(); };
const swapCities = () => {
    [trip.origin, trip.destination] = [trip.destination, trip.origin];
};

// ---------- Affichage ----------
const fmtTime = (iso) => (iso ? new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '');
const fmtDay = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) : 'Date a confirmer');
const price = (o) => `${Number(o.discounted_price ?? o.price).toLocaleString('fr-FR')} ${o.currency}`;

const partnersSubtitle = computed(() => ({
    bus: 'Compagnies de bus interurbains : Brazzaville, Pointe-Noire, Dolisie, Oyo, Ouesso...',
    bateau: 'Bateaux sur le fleuve Congo et ses affluents.',
    canot: 'Canots rapides et pirogues (Brazzaville - Kinshasa, fleuve Congo).',
    train: 'Ligne ferroviaire Congo-Ocean : Brazzaville - Dolisie - Pointe-Noire.',
}[mode.value] ?? 'Compagnies aeriennes, bus interurbains, train et canots rapides vers Kinshasa.'));
const partnersCategory = computed(() => ({ bus: 'transport_routier', bateau: 'canot_rapide', canot: 'canot_rapide', train: 'transport_ferroviaire', avion: 'compagnie_aerienne' }[mode.value] ?? ''));
</script>

<template>
    <Head title="Voyage" />

    <section class="relative h-[240px] overflow-hidden sm:h-[280px]">
        <img src="/images/hero.jpg" alt="Voyage" class="h-full w-full object-cover object-[center_20%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-12 text-left sm:px-10 sm:pt-14">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Voyage</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">Ou voulez-vous partir ?</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">Billets d'avion et circuits en bus, bateau, canot, train ou voiture.</p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-5xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                <div class="flex gap-2">
                    <button
                        v-for="t in tabs"
                        :key="t.value"
                        type="button"
                        class="rounded-full px-5 py-1.5 text-sm font-semibold transition"
                        :class="type === t.value ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                        @click="setType(t.value)"
                    >
                        {{ t.label }}
                    </button>
                </div>
                <Link v-if="user" href="/vols/mes-vols" class="text-sm font-semibold text-gold-600 hover:underline">Mes billets d'avion &rarr;</Link>
            </div>

            <!-- VOLS -->
            <div v-if="type === 'vol'" class="mt-4">
                <FlightSearchForm :airports="airports" />
            </div>

            <!-- CIRCUITS : style covoiturage (depart, arrivee, date, passagers) -->
            <form v-else-if="type === 'circuit'" class="mt-4 grid gap-2 md:grid-cols-[1fr_auto_1fr_auto_auto_auto]" @submit.prevent="go()">
                <input v-model="trip.origin" type="text" placeholder="Depart (ex. Brazzaville)" class="rounded-xl border-gray-200 text-sm" />
                <button type="button" class="hidden h-10 w-10 items-center justify-center self-center rounded-full border text-navy-700 hover:bg-navy-50 md:flex" title="Inverser" @click="swapCities">&#8646;</button>
                <input v-model="trip.destination" type="text" placeholder="Arrivee (ex. Pointe-Noire)" class="rounded-xl border-gray-200 text-sm" />
                <input v-model="trip.date" type="date" :min="today" class="rounded-xl border-gray-200 text-sm" />
                <input v-model.number="trip.passagers" type="number" min="1" max="20" title="Passagers" class="w-24 rounded-xl border-gray-200 text-sm" />
                <button type="submit" class="btn-gold px-8">Rechercher</button>
            </form>

            <!-- TOUS -->
            <form v-else class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="go()">
                <input v-model="trip.destination" type="text" placeholder="Destination (ex: Paris, Dubai...)" class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600" />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-6 py-10">
        <!-- CIRCUITS : filtres + tri -->
        <template v-if="type === 'circuit'">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    v-for="(label, key) in tripScopes"
                    :key="key"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                    :class="scope === key ? 'border-gold-600 bg-gold-600 text-navy-900' : 'border-gold-600/40 text-navy-700 hover:border-gold-600'"
                    @click="setScope(key)"
                >{{ label }}</button>
                <select v-model="departement" class="rounded-full border-gray-200 py-1.5 text-sm" @change="go()">
                    <option value="">Tous les departements</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                </select>
            </div>
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="(label, key) in transportModes"
                    :key="key"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                    :class="mode === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700 hover:border-navy-400'"
                    @click="setMode(key)"
                ><span class="mr-1">{{ MODE_ICONS[key] }}</span>{{ label }}</button>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-navy-900">{{ offers.total ?? offers.data.length }} trajet{{ (offers.total ?? offers.data.length) > 1 ? 's' : '' }} disponible{{ (offers.total ?? offers.data.length) > 1 ? 's' : '' }}</h2>
                <div class="flex gap-1 rounded-full bg-navy-50 p-1 text-xs font-semibold">
                    <button type="button" class="rounded-full px-3 py-1" :class="sort === 'heure' ? 'bg-navy-900 text-white' : 'text-navy-700'" @click="setSort('heure')">Plus tot</button>
                    <button type="button" class="rounded-full px-3 py-1" :class="sort === 'prix' ? 'bg-navy-900 text-white' : 'text-navy-700'" @click="setSort('prix')">Moins cher</button>
                </div>
            </div>

            <div class="space-y-3">
                <Link
                    v-for="o in offers.data"
                    :key="o.id"
                    :href="`/voyage/${o.slug}`"
                    class="block rounded-2xl border bg-white p-4 shadow-sm transition hover:shadow-md"
                >
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-navy-500">{{ fmtDay(o.departure_at) }}</p>
                    <div class="flex items-stretch justify-between gap-4">
                        <div class="flex gap-4">
                            <div class="flex flex-col items-end justify-between py-0.5 text-sm font-bold text-navy-900">
                                <span>{{ fmtTime(o.departure_at) || '--:--' }}</span>
                                <span class="text-xs font-medium text-navy-500">{{ o.duration_label }}</span>
                                <span class="text-transparent">.</span>
                            </div>
                            <div class="flex flex-col items-center py-1.5">
                                <span class="h-3 w-3 rounded-full border-2 border-navy-900 bg-white"></span>
                                <span class="w-0.5 flex-1 bg-navy-900/70"></span>
                                <span class="h-3 w-3 rounded-full border-2 border-navy-900 bg-navy-900"></span>
                            </div>
                            <div class="flex flex-col justify-between text-sm font-semibold text-navy-900">
                                <span>{{ o.origin_city }}<span v-if="o.departure_point" class="block text-xs font-normal text-navy-500">{{ o.departure_point }}</span></span>
                                <span class="mt-4">{{ o.destination_city }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end justify-between text-right">
                            <p class="text-lg font-extrabold text-gold-600">{{ price(o) }}</p>
                            <p v-if="o.discount_percent > 0" class="rounded-full bg-pink-600 px-2 py-0.5 text-[11px] font-bold text-white">-{{ o.discount_percent }}%</p>
                            <p v-if="o.seats_available !== null && o.seats_available !== undefined" class="text-xs text-navy-500">{{ o.seats_available }} place{{ o.seats_available > 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 border-t pt-3 text-xs text-navy-700">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-navy-50 text-sm">{{ MODE_ICONS[o.transport_mode] ?? '🧭' }}</span>
                        <span class="font-semibold">{{ o.airline || o.title }}</span>
                        <span class="text-navy-500">· {{ o.transport_mode_label }}</span>
                        <span v-if="o.trip_scope_label" class="text-navy-500">· {{ o.trip_scope_label }}</span>
                    </div>
                </Link>
            </div>

            <div v-if="!offers.data.length" class="mt-4 rounded-2xl border border-dashed bg-navy-50 p-10 text-center">
                <p class="font-bold text-navy-900">Aucun trajet ne correspond a votre recherche</p>
                <p class="mt-1 text-sm text-navy-700">Essayez une autre date ou un autre mode de transport, ou contactez directement nos transporteurs partenaires ci-dessous.</p>
            </div>
        </template>

        <!-- VOLS / TOUS : cartes photo -->
        <template v-else>
            <div v-if="type === 'vol'" class="mb-6 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">Offres vols du moment</h2>
                    <p class="text-sm text-navy-500">Ou lancez une recherche ci-dessus pour comparer les vols et reserver votre billet.</p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
                    </div>
                    <div class="p-4">
                        <p class="font-semibold text-navy-900">{{ offer.title }}</p>
                        <p class="text-xs uppercase text-navy-500">{{ [offer.type === 'circuit' ? offer.transport_mode_label : offer.type, offer.airline].filter(Boolean).join(' - ') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gold-600">{{ price(offer) }}</p>
                    </div>
                </Link>
            </div>
            <p v-if="!offers.data.length" class="mt-10 text-center text-navy-500">Aucune offre pour le moment.</p>
        </template>

        <div v-if="offers.links && offers.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1">
            <Link
                v-for="l in offers.links"
                :key="l.label"
                :href="l.url || '#'"
                preserve-scroll
                class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                :class="[l.active ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50', !l.url && 'pointer-events-none opacity-40']"
                v-html="l.label"
            />
        </div>

        <PartnersSection :key="mode" module="voyage" title="Compagnies & transport" :subtitle="partnersSubtitle" :category="partnersCategory" />
    </div>
</template>
