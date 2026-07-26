<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({ offers: { type: Object, required: true } });

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };

// Public tracking box (no login required) - DHL-style.
const trackingCode = ref('');
const track = () => {
    if (!trackingCode.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(trackingCode.value.trim())}`);
};

// "Palettes & marchandises" - existing partner-published freight offers,
// filterable by transport mode.
const activeMode = ref('all');
const modes = [
    { value: 'all', label: 'Tous' },
    { value: 'air', label: 'Aerien' },
    { value: 'mer', label: 'Maritime' },
    { value: 'route', label: 'Routier' },
];
const filteredOffers = computed(() =>
    activeMode.value === 'all' ? props.offers.data : props.offers.data.filter((o) => o.mode === activeMode.value),
);

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
    <Head title="Fret" />

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

        <!-- Palettes & marchandises -->
        <section class="mt-14">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">Palettes &amp; marchandises</h2>
                    <p class="mt-1 text-sm text-navy-700">Transport professionnel : fret aerien, maritime et routier par nos partenaires transporteurs.</p>
                </div>
                <div class="flex gap-1 rounded-full bg-navy-50 p-1">
                    <button
                        v-for="m in modes"
                        :key="m.value"
                        type="button"
                        class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                        :class="activeMode === m.value ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-100'"
                        @click="activeMode = m.value"
                    >
                        {{ m.label }}
                    </button>
                </div>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="o in filteredOffers" :key="o.id" :href="`/fret/${o.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                    <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} &rarr; {{ o.destination_city }}</ListingImage>
                    <div class="p-4">
                        <p class="font-semibold text-navy-900">{{ o.title }}</p>
                        <p class="mt-1 text-xs uppercase text-navy-500">{{ modeLabel[o.mode] }} - {{ o.origin_city }} &rarr; {{ o.destination_city }}</p>
                        <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(o.price_per_kg).toLocaleString('fr-FR') }} {{ o.currency }} / kg</p>
                    </div>
                </Link>
            </div>
            <p v-if="!filteredOffers.length" class="mt-10 text-center text-navy-500">Aucune offre de fret pour ce mode.</p>
        </section>
    </div>
</template>
