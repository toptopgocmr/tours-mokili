<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };
const modeIcon = { air: 'M2 16l20-8-8 12-3-5-9 1z', mer: 'M3 17l2 3h14l2-3H3zm3-3V8h5V5h2v3h5v6H6z', route: 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM7 18a2 2 0 100-.01M17 18a2 2 0 100-.01' };

/* ---- Onglets "hero" a la DHL : Suivre / Devis / Tarif ---- */
const tab = ref('track');
const tabs = [
    { key: 'track', label: 'Suivre un colis' },
    { key: 'quote', label: 'Demander un devis' },
    { key: 'rate', label: 'Estimer un tarif' },
];

const trackingCode = ref('');
const track = () => {
    if (!trackingCode.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(trackingCode.value.trim())}`);
};

/* ---- Estimation indicative (colis / documents) ---- */
const parcelRates = {
    document: { label: 'Documents', perKg: 1500, base: 2000 },
    colis: { label: 'Colis standard', perKg: 2500, base: 3000 },
    express: { label: 'Colis express', perKg: 4000, base: 5000 },
};
const parcelType = ref('colis');
const parcelWeight = ref(1);
const parcelEstimate = computed(() => {
    const rate = parcelRates[parcelType.value];
    return Math.round(rate.base + rate.perKg * (Number(parcelWeight.value) || 0));
});

/* ---- Filtres catalogue (cote serveur) ---- */
const f = reactive({
    scope: props.filters.scope || '',
    mode: props.filters.mode || '',
    origin: props.filters.origin || '',
    destination: props.filters.destination || '',
});
const search = () => {
    const q = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
    router.get('/fret', q, { preserveScroll: true, preserveState: true });
};
const setFilter = (key, value) => {
    f[key] = f[key] === value ? '' : value;
    search();
};

const steps = [
    { n: 1, t: 'Decrivez votre envoi', d: 'Trajet, poids, type de marchandise : 2 minutes suffisent.' },
    { n: 2, t: 'Comparez les devis', d: 'Des partenaires verifies vous repondent avec un tarif detaille.' },
    { n: 3, t: 'Payez en toute securite', d: 'Mobile Money ou carte. Votre reservation est confirmee.' },
    { n: 4, t: 'Suivez en temps reel', d: 'Chaque etape, de l\'enlevement a la livraison.' },
];
const perks = [
    { t: 'Partenaires verifies', d: 'Chaque transporteur est controle par notre equipe.' },
    { t: 'Prix transparents', d: 'Inclusions, exclusions et validite affichees avant paiement.' },
    { t: 'Suivi sans compte', d: 'Votre code de suivi suffit pour savoir ou est votre colis.' },
    { t: 'Support reclamation', d: 'Un litige ? Ouvrez une reclamation depuis votre commande.' },
];
</script>

<template>
    <Head title="Fret & logistique" />

    <!-- HERO -->
    <section class="relative bg-navy-900">
        <div class="absolute inset-x-0 bottom-0 h-1.5 bg-gold-500"></div>
        <div class="mx-auto max-w-7xl px-6 pb-24 pt-12">
            <p class="text-xs font-bold uppercase tracking-widest text-gold-500">Fret &amp; logistique</p>
            <h1 class="mt-2 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                Envoyez, suivez, livrez. Partout en Afrique et dans le monde.
            </h1>

            <div class="mt-8 max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex border-b">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="flex-1 px-4 py-4 text-sm font-bold transition sm:flex-none sm:px-8"
                        :class="tab === t.key ? 'border-b-4 border-gold-500 text-navy-900' : 'text-navy-500 hover:bg-navy-50'"
                        @click="tab = t.key"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <div class="p-6">
                    <form v-if="tab === 'track'" class="flex flex-col gap-3 sm:flex-row" @submit.prevent="track">
                        <input v-model="trackingCode" type="text" placeholder="Entrez votre code de suivi (ex. FRT-XXXXXXXXXX)" class="w-full rounded-lg border-gray-300 py-3 text-sm uppercase" />
                        <button type="submit" class="btn-gold whitespace-nowrap px-8 py-3">Suivre</button>
                    </form>

                    <div v-else-if="tab === 'quote'" class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-navy-700">Decrivez votre expedition une seule fois : nos partenaires verifies vous repondent avec un devis.</p>
                        <Link href="/fret/demandes/creer" class="btn-gold whitespace-nowrap px-8 py-3">Demander un devis</Link>
                    </div>

                    <div v-else class="grid items-center gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-xs font-semibold uppercase text-navy-500">Type d'envoi</label>
                            <select v-model="parcelType" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="(r, key) in parcelRates" :key="key" :value="key">{{ r.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase text-navy-500">Poids (kg)</label>
                            <input v-model.number="parcelWeight" type="number" min="0.1" step="0.1" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="rounded-xl bg-navy-50 p-4 text-center">
                            <p class="text-[11px] uppercase text-navy-500">Estimation indicative</p>
                            <p class="text-2xl font-extrabold text-gold-600">{{ parcelEstimate.toLocaleString('fr-FR') }} XAF</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-1 border-t bg-gray-50 px-6 py-3 text-xs font-semibold text-navy-700">
                    <Link href="/fret/mes-envois" class="hover:underline">Mes expeditions</Link>
                    <Link href="/fret/commandes" class="hover:underline">Mes commandes</Link>
                    <Link href="/fret/demandes" class="hover:underline">Mes demandes de devis</Link>
                </div>
            </div>
        </div>
    </section>

    <!-- COMMENT CA MARCHE -->
    <section class="mx-auto -mt-12 max-w-7xl px-6">
        <div class="grid gap-4 rounded-2xl bg-white p-6 shadow-lg sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="s in steps" :key="s.n" class="flex gap-3">
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gold-500 text-sm font-extrabold text-navy-900">{{ s.n }}</span>
                <div>
                    <p class="text-sm font-bold text-navy-900">{{ s.t }}</p>
                    <p class="mt-0.5 text-xs text-navy-500">{{ s.d }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-14">
        <!-- CATALOGUE -->
        <section>
            <h2 class="text-2xl font-extrabold text-navy-900">Offres de nos partenaires</h2>
            <p class="mt-1 text-sm text-navy-700">Fret aerien, maritime et routier : documents, marchandises, vehicules, conteneurs.</p>

            <div class="mt-6 rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold uppercase text-navy-500">Zone</span>
                        <button v-for="z in [['national', 'National'], ['international', 'International']]" :key="z[0]" type="button"
                            class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                            :class="f.scope === z[0] ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                            @click="setFilter('scope', z[0])">{{ z[1] }}</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold uppercase text-navy-500">Transport</span>
                        <button v-for="(label, key) in modeLabel" :key="key" type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                            :class="f.mode === key ? 'border-gold-500 bg-gold-500 text-navy-900' : 'text-navy-700 hover:bg-navy-50'"
                            @click="setFilter('mode', key)">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path :d="modeIcon[key]" /></svg>
                            {{ label }}
                        </button>
                    </div>
                </div>
                <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="search">
                    <input v-model="f.origin" type="text" placeholder="Depart (ex. Brazzaville)" class="w-full rounded-lg border-gray-300 text-sm" />
                    <input v-model="f.destination" type="text" placeholder="Arrivee (ex. Douala, Guangzhou)" class="w-full rounded-lg border-gray-300 text-sm" />
                    <button type="submit" class="btn-gold whitespace-nowrap px-8">Rechercher</button>
                </form>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="o in offers.data" :key="o.id" :href="`/fret/${o.slug}`" class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative">
                        <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} &rarr; {{ o.destination_city }}</ListingImage>
                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-bold text-navy-900 shadow">{{ modeLabel[o.mode] }}</span>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase text-navy-500">{{ o.origin_city }} &rarr; {{ o.destination_city }}</p>
                        <p class="mt-1 font-bold text-navy-900">{{ o.title }}</p>
                        <p v-if="o.partner_profile" class="mt-1 flex items-center gap-1 text-xs text-navy-500">
                            <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" /></svg>
                            {{ o.partner_profile.trade_name || o.partner_profile.company_name }} - partenaire verifie
                        </p>
                        <div class="mt-3 flex items-end justify-between border-t pt-3">
                            <span class="text-xs text-navy-500">
                                <template v-if="o.transit_days_max">Delai {{ o.transit_days_min ? o.transit_days_min + ' a ' : '' }}{{ o.transit_days_max }} j</template>
                            </span>
                            <span class="text-right text-sm font-extrabold text-gold-600">
                                <template v-if="o.pricing_mode === 'quote'">Sur devis</template>
                                <template v-else>{{ o.price_is_firm ? '' : 'des ' }}{{ Number(o.price_per_kg).toLocaleString('fr-FR') }} {{ o.currency }}/{{ o.unit || 'kg' }}</template>
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-if="!offers.data.length" class="mt-8 rounded-2xl border border-dashed bg-navy-50 p-10 text-center">
                <p class="font-bold text-navy-900">Aucune offre ne correspond a ces criteres</p>
                <p class="mt-1 text-sm text-navy-700">Decrivez votre envoi : nos partenaires vous repondent par un devis detaille.</p>
                <Link href="/fret/demandes/creer" class="btn-gold mt-4 inline-block px-8">Demander un devis</Link>
            </div>

            <div v-if="offers.links && offers.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1">
                <Link v-for="l in offers.links" :key="l.label" :href="l.url || '#'" preserve-scroll
                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                    :class="[l.active ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50', !l.url && 'pointer-events-none opacity-40']"
                    v-html="l.label" />
            </div>
        </section>

        <!-- POURQUOI MOKILI -->
        <section class="mt-16 rounded-2xl bg-navy-900 p-8">
            <h2 class="text-xl font-extrabold text-white">Pourquoi expedier avec MOKILI TOUR</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="p in perks" :key="p.t" class="border-l-2 border-gold-500 pl-4">
                    <p class="text-sm font-bold text-white">{{ p.t }}</p>
                    <p class="mt-1 text-xs text-navy-200">{{ p.d }}</p>
                </div>
            </div>
        </section>
    </div>
</template>
