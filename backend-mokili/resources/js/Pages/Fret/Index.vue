<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

// FRET & LOGISTIQUE : national / international, maritime / aerien /
// routier, type de colis (documents, marchandise, vehicule, conteneur...).
const fret = computed(() => usePage().props.catalogue?.fret ?? {});
const f = reactive({
    scope: props.filters.scope ?? '', mode: props.filters.mode ?? '', colis: props.filters.colis ?? '',
    origine: props.filters.origine ?? '', destination: props.filters.destination ?? '',
});
const MODE_ICONS = { mer: '🚢', air: '✈️', route: '🚛' };
const PACKAGE_ICONS = { documents: '📄', marchandise: '📦', vehicule: '🚗', conteneur: '🚢', autre: '🧳' };
const applyFilters = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
    router.get('/fret', query, { preserveState: true, preserveScroll: true, replace: true });
};
const toggle = (key, value) => {
    f[key] = f[key] === value ? '' : value;
    applyFilters();
};

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };

// Public tracking box (no login required) - DHL-style.
const trackingCode = ref('');
const track = () => {
    if (!trackingCode.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(trackingCode.value.trim())}`);
};


// "Colis & documents" - simple indicative quote calculator (individuals /
// small parcels), separate from the freight-forwarding catalogue below,
// mirroring DHL Express (documents/parcels) vs Global Forwarding (pallets).
const parcelRates = {
    document: { label: 'Documents', perKg: 1500, base: 2000 },
    colis: { label: 'Colis standard', perKg: 2500, base: 3000 },
    express: { label: 'Colis express', perKg: 4000, base: 5000 },
};
const parcelType = ref('colis');
const parcelWeight = ref(1);
const parcelEstimate = computed(() => {
    const rate = parcelRates[parcelType.value];
    const weight = Number(parcelWeight.value) || 0;
    return Math.round(rate.base + rate.perKg * weight);
});
</script>

<template>
    <Head title="Fret & logistique" />

    <div class="bg-navy-900 py-8">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h1 class="text-2xl font-bold text-white">Suivez votre expedition</h1>
            <p class="mt-1 text-sm text-navy-200">Entrez votre code de suivi, sans connexion requise.</p>
            <form class="mt-5 flex gap-2" @submit.prevent="track">
                <input
                    v-model="trackingCode"
                    type="text"
                    placeholder="Code de suivi (ex. FRT-XXXXXXXXXX)"
                    class="w-full rounded-lg border-gray-300 text-sm uppercase"
                />
                <button type="submit" class="btn-gold whitespace-nowrap px-6">Suivre</button>
            </form>
            <Link href="/fret/mes-envois" class="mt-3 inline-block text-xs font-semibold text-navy-200 hover:underline">
                Voir toutes mes expeditions &rarr;
            </Link>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-12">
        <!-- Colis & documents -->
        <section>
            <h2 class="text-xl font-bold text-navy-900">Colis &amp; documents</h2>
            <p class="mt-1 text-sm text-navy-700">Pour les particuliers : documents, petits colis, envois express.</p>

            <div class="mt-5 grid gap-6 rounded-2xl border p-6 shadow-sm md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-navy-900">Type d'envoi</label>
                        <select v-model="parcelType" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option v-for="(r, key) in parcelRates" :key="key" :value="key">{{ r.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-navy-900">Poids estime (kg)</label>
                        <input v-model.number="parcelWeight" type="number" min="0.1" step="0.1" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                </div>
                <div class="flex flex-col justify-center rounded-xl bg-navy-50 p-5">
                    <p class="text-xs uppercase text-navy-500">Estimation indicative</p>
                    <p class="mt-1 text-3xl font-bold text-gold-600">{{ parcelEstimate.toLocaleString('fr-FR') }} XAF</p>
                    <p class="mt-2 text-xs text-navy-500">
                        Tarif confirme au moment du depot. Contactez un point MOKILI TOUR ou un partenaire fret pour organiser l'enlevement.
                    </p>
                </div>
            </div>
        </section>

        <!-- Fret & logistique : offres des transporteurs -->
        <section class="mt-14">
            <h2 class="text-xl font-bold text-navy-900">Fret &amp; logistique</h2>
            <p class="mt-1 text-sm text-navy-700">National et international, par voie maritime, aerienne ou routiere : documents, marchandises, vehicules, conteneurs.</p>

            <div class="mt-5 space-y-3 rounded-2xl border p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="w-28 text-xs font-semibold uppercase text-navy-500">Zone</span>
                    <button v-for="(label, key) in fret.scopes" :key="key" type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold transition" :class="f.scope === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700'" @click="toggle('scope', key)">{{ label }}</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="w-28 text-xs font-semibold uppercase text-navy-500">Transport</span>
                    <button v-for="(label, key) in fret.modes" :key="key" type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold transition" :class="f.mode === key ? 'border-teal-700 bg-teal-700 text-white' : 'border-gray-200 text-navy-700'" @click="toggle('mode', key)">{{ MODE_ICONS[key] }} {{ label }}</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="w-28 text-xs font-semibold uppercase text-navy-500">Type de colis</span>
                    <button v-for="(label, key) in fret.package_types" :key="key" type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold transition" :class="f.colis === key ? 'border-gold-600 bg-gold-600 text-navy-900' : 'border-gray-200 text-navy-700'" @click="toggle('colis', key)">{{ PACKAGE_ICONS[key] }} {{ label }}</button>
                </div>
                <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="applyFilters">
                    <input v-model="f.origine" type="text" placeholder="Depart (ex. Brazzaville)" class="flex-1 rounded-full border-gray-200 text-sm" />
                    <input v-model="f.destination" type="text" placeholder="Arrivee (ex. Douala, Guangzhou)" class="flex-1 rounded-full border-gray-200 text-sm" />
                    <button type="submit" class="btn-gold sm:px-8">Rechercher</button>
                </form>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="o in offers.data" :key="o.id" :href="`/fret/${o.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                    <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} &rarr; {{ o.destination_city }}</ListingImage>
                    <div class="p-4">
                        <p class="font-semibold text-navy-900">{{ o.title }}</p>
                        <p class="mt-1 text-xs uppercase text-navy-500">{{ MODE_ICONS[o.mode] }} {{ modeLabel[o.mode] }} · {{ o.scope === 'national' ? 'National' : 'International' }} · {{ o.origin_city }} &rarr; {{ o.destination_city }}</p>
                        <p class="mt-1 text-xs text-navy-600">{{ o.package_type_labels?.join(', ') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(o.price_per_kg).toLocaleString('fr-FR') }} {{ o.currency }} / kg</p>
                    </div>
                </Link>
            </div>
            <p v-if="!offers.data.length" class="mt-10 text-center text-navy-500">Aucune offre de fret pour ces criteres.</p>
            <Paginator :page="offers" />
        </section>
    </div>
</template>
