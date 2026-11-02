<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import FlightOfferCard from '@/Components/FlightOfferCard.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { longDate } from '@/utils/flights';

defineOptions({ layout: MainLayout });

const props = defineProps({
    search: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    offers: { type: Array, default: () => [] },
    airlines: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    expired: { type: Boolean, default: false },
    supplierErrors: { type: Object, default: null },
    cabins: { type: Object, default: () => ({}) },
});

const showForm = ref(false);
const pick = (k, d) => (typeof props.filters?.[k] === 'string' ? props.filters[k] : d);
const f = reactive({ stops: pick('stops', ''), airline: pick('airline', ''), sort: pick('sort', 'price') });

const apply = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null));
    router.get(`/voyage/vols/resultats/${props.search.uuid}`, query, { preserveState: true, preserveScroll: true, replace: true });
};

const city = (code) => props.airports?.[code]?.city ?? code;
const labels = computed(() => ({
    origin: props.airports?.[props.search.origin] ? `${city(props.search.origin)} (${props.search.origin})` : props.search.origin,
    destination: props.airports?.[props.search.destination] ? `${city(props.search.destination)} (${props.search.destination})` : props.search.destination,
}));
const paxText = computed(() => {
    const s = props.search;
    return [`${s.adults} adulte(s)`, s.children ? `${s.children} enfant(s)` : null, s.infants ? `${s.infants} bebe(s)` : null].filter(Boolean).join(', ');
});
const refresh = () => router.get('/voyage/vols/recherche', {
    trip_type: props.search.trip_type, origin: props.search.origin, destination: props.search.destination,
    depart_date: props.search.depart_date?.slice(0, 10), return_date: props.search.return_date?.slice(0, 10) ?? undefined,
    adults: props.search.adults, children: props.search.children, infants: props.search.infants, cabin: props.search.cabin,
});
</script>

<template>
    <Head :title="`Vols ${city(search.origin)} - ${city(search.destination)}`" />

    <section class="bg-navy-900 px-6 py-6 text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-gold-600">Billets d'avion</p>
                <h1 class="text-xl font-bold sm:text-2xl">
                    {{ city(search.origin) }} {{ search.trip_type === 'roundtrip' ? '⇄' : '→' }} {{ city(search.destination) }}
                </h1>
                <p class="text-sm text-white/80">
                    {{ longDate(search.depart_date) }}<span v-if="search.return_date"> · retour {{ longDate(search.return_date) }}</span>
                    · {{ paxText }} · {{ cabins[search.cabin] ?? search.cabin }}
                </p>
            </div>
            <button type="button" class="btn-outline !border-white !text-white hover:!bg-white/10" @click="showForm = !showForm">Modifier la recherche</button>
        </div>
        <div v-if="showForm" class="mx-auto mt-4 max-w-6xl rounded-2xl bg-white p-4 text-navy-900">
            <FlightSearchForm :initial="search" :labels="labels" :cabins="cabins" />
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-8 px-6 py-8 lg:grid-cols-[230px_1fr]">
        <aside class="space-y-6 text-sm">
            <div>
                <p class="mb-2 font-semibold text-navy-900">Trier par</p>
                <select v-model="f.sort" class="w-full rounded-lg border-gray-200 text-sm" @change="apply">
                    <option value="price">Prix le plus bas</option>
                    <option value="duration">Duree la plus courte</option>
                    <option value="departure">Heure de depart</option>
                </select>
            </div>
            <div>
                <p class="mb-2 font-semibold text-navy-900">Escales</p>
                <label v-for="o in [{ v: '', l: 'Toutes' }, { v: '0', l: 'Direct uniquement' }, { v: '1', l: '1 escale max' }]" :key="o.v" class="flex items-center gap-2 py-0.5">
                    <input v-model="f.stops" type="radio" :value="o.v" @change="apply" /> {{ o.l }}
                </label>
            </div>
            <div v-if="airlines.length > 1">
                <p class="mb-2 font-semibold text-navy-900">Compagnies</p>
                <label class="flex items-center gap-2 py-0.5"><input v-model="f.airline" type="radio" value="" @change="apply" /> Toutes</label>
                <label v-for="a in airlines" :key="a.code" class="flex items-center gap-2 py-0.5">
                    <input v-model="f.airline" type="radio" :value="a.code" @change="apply" /> {{ a.name }}
                </label>
            </div>
            <p class="rounded-xl bg-navy-50/60 p-3 text-xs text-navy-600">
                Les prix sont revalides aupres de la compagnie avant le paiement. Votre billet electronique est envoye uniquement apres son emission.
            </p>
        </aside>

        <div class="space-y-4">
            <div v-if="expired" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <span>Ces prix datent de plus de 30 minutes et peuvent avoir change.</span>
                <button type="button" class="font-semibold underline" @click="refresh">Actualiser les prix</button>
            </div>
            <div v-if="supplierErrors" class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-navy-600">
                Certains fournisseurs n'ont pas repondu : les resultats peuvent etre incomplets.
            </div>

            <p v-if="offers.length" class="text-sm text-navy-600">{{ offers.length }} vol(s) trouve(s)</p>
            <FlightOfferCard v-for="o in offers" :key="o.uuid" :offer="o" :airports="airports" />

            <div v-if="!offers.length" class="rounded-2xl border p-10 text-center">
                <p class="text-lg font-semibold text-navy-900">Aucun vol disponible pour ces criteres</p>
                <p class="mt-2 text-sm text-navy-600">
                    Essayez d'autres dates, une autre classe ou un aeroport voisin. Notre equipe peut aussi vous trouver un billet :
                    <a href="/#contact" class="font-semibold text-gold-600 hover:underline">contactez la billetterie</a>.
                </p>
                <button type="button" class="btn-gold mt-4" @click="showForm = true">Modifier la recherche</button>
            </div>
        </div>
    </div>
</template>
