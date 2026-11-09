<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offers: { type: Object, required: true },
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

// Icones des moyens de transport des circuits.
const MODE_ICONS = { avion: '✈️', bus: '🚌', bateau: '⛴️', canot: '🚤', train: '🚆', voiture: '🚙', combine: '🧭' };
const modeChips = computed(() => [{ value: '', label: 'Tous les transports', icon: '' },
    ...Object.entries(props.transportModes).map(([value, label]) => ({ value, label, icon: MODE_ICONS[value] ?? '' }))]);

// Partenaires transport affiches selon le moyen choisi.
const partnersSubtitle = computed(() => ({
    bus: 'Compagnies de bus interurbains : Brazzaville, Pointe-Noire, Dolisie, Oyo, Ouesso...',
    bateau: 'Bateaux sur le fleuve Congo et ses affluents.',
    canot: 'Canots rapides et pirogues (Brazzaville - Kinshasa, fleuve Congo).',
    train: 'Ligne ferroviaire Congo-Ocean : Brazzaville - Dolisie - Pointe-Noire.',
}[mode.value] ?? 'Compagnies aeriennes, bus interurbains, train et canots rapides vers Kinshasa.'));

const types = [
    { value: '', label: 'Tous' },
    { value: 'vol', label: 'Vols' },
    { value: 'sejour', label: 'Sejours' },
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
    <section class="relative h-[260px] overflow-hidden sm:h-[300px]">
        <img src="/images/hero.jpg" alt="Voyage" class="h-full w-full object-cover object-[center_20%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-14 text-left sm:px-10 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Voyage</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">Ou voulez-vous partir ?</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">
                Billets d'avion, sejours et circuits en avion, bus, bateau ou train.
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
            </div>
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

    <div class="mx-auto max-w-7xl px-6 py-10">
        <!-- CIRCUITS : portee (ville / inter-departements / international) + departement -->
        <div v-if="type === 'circuit'" class="mb-3 flex flex-wrap items-center gap-2">
            <button
                v-for="(label, key) in tripScopes"
                :key="key"
                type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                :class="scope === key ? 'border-gold-600 bg-gold-600 text-navy-900' : 'border-gold-600/40 text-navy-700 hover:border-gold-600'"
                @click="setScope(key)"
            >
                {{ label }}
            </button>
            <select v-model="departement" class="rounded-full border-gray-200 py-1.5 text-sm" @change="search">
                <option value="">Tous les departements</option>
                <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
            </select>
        </div>
        <!-- CIRCUITS : choix du moyen de transport -->
        <div v-if="type === 'circuit'" class="mb-6 flex flex-wrap gap-2">
            <button
                v-for="m in modeChips"
                :key="m.value"
                type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                :class="mode === m.value ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700 hover:border-navy-400'"
                @click="setMode(m.value)"
            >
                <span v-if="m.icon" class="mr-1">{{ m.icon }}</span>{{ m.label }}
            </button>
        </div>

        <div v-if="type === 'vol'" class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-xl font-bold text-navy-900">Offres vols du moment</h2>
                <p class="text-sm text-navy-500">Ou lancez une recherche ci-dessus pour comparer les vols et reserver votre billet.</p>
            </div>
            <Link href="/voyage/vols/mes-billets" class="text-sm font-semibold text-gold-600 hover:underline">Mes billets d'avion →</Link>
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

        <p v-if="!offers.data.length" class="mt-10 text-center text-navy-500">
            {{ type === 'circuit' && mode ? `Aucun circuit en ${(transportModes[mode] ?? mode).toLowerCase()} pour le moment : contactez nos transporteurs partenaires ci-dessous.` : 'Aucune offre pour le moment.' }}
        </p>
        <PartnersSection
            v-if="type === 'vol'"
            key="vol"
            module="voyage"
            only="compagnie_aerienne"
            title="Compagnies aeriennes"
            subtitle="Les compagnies partenaires de notre billetterie."
        />
        <PartnersSection v-else :key="mode" module="voyage" title="Compagnies & transport" :subtitle="partnersSubtitle" :category="{ bus: 'transport_routier', bateau: 'canot_rapide', canot: 'canot_rapide', train: 'transport_ferroviaire', avion: 'compagnie_aerienne' }[mode] ?? ''" />
    </div>
</template>
