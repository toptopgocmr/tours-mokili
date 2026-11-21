<script setup>
import PhotoHero from '@/Components/PhotoHero.vue';
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
const applyFilters = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
    router.get('/fret', query, { preserveState: true, preserveScroll: true, replace: true });
};
const toggle = (key, value) => {
    f[key] = f[key] === value ? '' : value;
    applyFilters();
};
const swapCities = () => {
    [f.origine, f.destination] = [f.destination, f.origine];
};
const hasFilters = computed(() => Object.values(f).some(Boolean));
const resetFilters = () => {
    Object.keys(f).forEach((k) => (f[k] = ''));
    applyFilters();
};

// Icones plates (Material) a la place des emojis.
const ICONS = {
    mer: 'M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19zM6 6h12v3.97L12 8 6 9.97V6z',
    air: 'M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z',
    route: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z',
    documents: 'M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z',
    marchandise: 'M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.69V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.7c.57-.35 1-.97 1-1.69V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm5-7H4V4l16-.02V7z',
    vehicule: 'M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z',
    conteneur: 'M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm2 3v8h1.6V8H5zm3.6 0v8h1.6V8H8.6zm3.6 0v8h1.6V8h-1.6zm3.6 0v8h1.6V8h-1.6z',
    autre: 'M17 6h-2V3c0-.55-.45-1-1-1h-4c-.55 0-1 .45-1 1v3H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2 0 .55.45 1 1 1s1-.45 1-1h6c0 .55.45 1 1 1s1-.45 1-1c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM9.5 18H8V9h1.5v9zm3.25 0h-1.5V9h1.5v9zm.75-12h-3V3.5h3V6zM16 18h-1.5V9H16v9z',
    express: 'M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66.19-.34.05-.08.07-.12C8.48 10.94 10.42 7.54 13 3h1l-1 7h3.5c.49 0 .56.33.47.51l-.07.15C12.96 17.55 11 21 11 21z',
    national: 'M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z',
    international: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
};
const MODE_STYLE = {
    mer: 'bg-sky-50 text-sky-700 ring-sky-200',
    air: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    route: 'bg-amber-50 text-amber-800 ring-amber-200',
};
const modeLabel = computed(() => fret.value.modes ?? { air: 'Aérien', mer: 'Maritime', route: 'Routier' });

// Suivi public (sans connexion).
const trackingCode = ref('');
const track = () => {
    if (!trackingCode.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(trackingCode.value.trim())}`);
};

// "Colis & documents" : estimation indicative pour les particuliers
// (forfait + prix au kg), distincte du catalogue des transporteurs.
const parcelRates = {
    document: { label: 'Documents', hint: 'Plis, dossiers, contrats', icon: 'documents', perKg: 1500, base: 2000 },
    colis: { label: 'Colis standard', hint: 'Petits et moyens colis', icon: 'marchandise', perKg: 2500, base: 3000 },
    express: { label: 'Colis express', hint: 'Livraison prioritaire', icon: 'express', perKg: 4000, base: 5000 },
};
const parcelType = ref('colis');
const parcelWeight = ref(1);
const weights = [0.5, 1, 2, 5, 10, 20];
const step = (d) => {
    parcelWeight.value = Math.max(0.5, Math.round(((Number(parcelWeight.value) || 0) + d) * 10) / 10);
};
const rate = computed(() => parcelRates[parcelType.value]);
const weight = computed(() => Math.max(0, Number(parcelWeight.value) || 0));
const parcelEstimate = computed(() => Math.round(rate.value.base + rate.value.perKg * weight.value));
const howItWorks = [
    { t: 'Estimez', d: 'Le prix selon le type et le poids.' },
    { t: 'Déposez', d: 'Chez un point MOKILI TOUR ou un partenaire.' },
    { t: 'Suivez', d: 'Votre colis avec son code de suivi.' },
];
const money = (x) => Number(x ?? 0).toLocaleString('fr-FR');
const goOffers = () => document.getElementById('offres')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
</script>

<template>
    <Head title="Fret & logistique" />

    <!-- En-tete : titre + suivi de colis -->
    <PhotoHero tag="div" class="pb-20 pt-12" veil="left">
        <div class="mx-auto max-w-7xl px-6">
            <p class="text-xs font-bold uppercase tracking-widest text-gold-500">Fret &amp; logistique</p>
            <h1 class="mt-2 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">Expédiez partout, suivez en temps réel</h1>
            <p class="mt-2 max-w-xl text-sm text-navy-100 sm:text-base">Documents, colis, marchandises, véhicules et conteneurs : par mer, par air ou par route, au Congo et à l'international.</p>

            <form class="mt-6 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 shadow-xl sm:flex-row" @submit.prevent="track">
                <label class="flex flex-1 items-center gap-2 px-3">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-navy-400" fill="currentColor"><path :d="ICONS.route" /></svg>
                    <input
                        v-model="trackingCode"
                        type="text"
                        placeholder="Code de suivi (ex. FRT-XXXXXXXXXX)"
                        class="w-full border-0 py-3 text-sm uppercase placeholder:normal-case focus:ring-0"
                    />
                </label>
                <button type="submit" class="btn-gold whitespace-nowrap px-8">Suivre mon colis</button>
            </form>
            <Link href="/fret/mes-envois" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-white/90 hover:text-gold-400">
                Voir toutes mes expéditions <span aria-hidden="true">→</span>
            </Link>
        </div>
    </PhotoHero>

    <div class="mx-auto -mt-12 max-w-7xl px-4 pb-12 sm:px-6">
        <!-- Colis & documents : estimation -->
        <section class="relative rounded-3xl bg-white p-5 shadow-xl ring-1 ring-black/5 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-xl font-extrabold text-navy-900 sm:text-2xl">Estimez l'envoi d'un colis</h2>
                    <p class="mt-1 text-sm text-navy-600">Pour les particuliers : documents, petits colis et envois express.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="min-w-0 space-y-6">
                    <div>
                        <p class="text-sm font-semibold text-navy-900">Type d'envoi</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="(r, key) in parcelRates"
                                :key="key"
                                type="button"
                                class="flex items-start gap-3 rounded-2xl border-2 p-4 text-left transition"
                                :class="parcelType === key ? 'border-navy-900 bg-navy-50/60' : 'border-slate-200 hover:border-navy-300'"
                                @click="parcelType = key"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="parcelType === key ? 'bg-navy-900 text-gold-500' : 'bg-slate-100 text-navy-700'">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path :d="ICONS[r.icon]" /></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-bold text-navy-900">{{ r.label }}</span>
                                    <span class="block text-xs text-slate-500">{{ r.hint }}</span>
                                    <span class="mt-1 block text-xs font-semibold text-gold-700">{{ money(r.perKg) }} FCFA / kg</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-navy-900">Poids estimé</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <div class="flex items-center overflow-hidden rounded-xl border border-slate-300">
                                <button type="button" class="h-11 w-11 text-xl font-bold text-navy-900 hover:bg-slate-100" aria-label="Moins" @click="step(-0.5)">−</button>
                                <input v-model.number="parcelWeight" type="number" min="0.5" step="0.5" class="h-11 w-20 border-0 border-x border-slate-300 text-center text-base font-bold focus:ring-0" />
                                <button type="button" class="h-11 w-11 text-xl font-bold text-navy-900 hover:bg-slate-100" aria-label="Plus" @click="step(0.5)">+</button>
                            </div>
                            <span class="text-sm font-semibold text-slate-500">kg</span>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="w in weights"
                                    :key="w"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                    :class="weight === w ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 text-navy-700 hover:border-navy-300'"
                                    @click="parcelWeight = w"
                                >{{ String(w).replace('.', ',') }} kg</button>
                            </div>
                        </div>
                    </div>

                    <ol class="grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-3">
                        <li v-for="(st, i) in howItWorks" :key="st.t" class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gold-500 text-sm font-extrabold text-navy-900">{{ i + 1 }}</span>
                            <span>
                                <span class="block text-sm font-bold text-navy-900">{{ st.t }}</span>
                                <span class="block text-xs text-slate-500">{{ st.d }}</span>
                            </span>
                        </li>
                    </ol>
                </div>

                <div class="flex flex-col justify-between rounded-2xl bg-gradient-to-br from-navy-700 to-navy-900 p-6 text-white">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-white/60">Estimation indicative</p>
                        <p class="mt-1 text-4xl font-extrabold text-gold-400">{{ money(parcelEstimate) }} <span class="text-lg font-bold text-white">FCFA</span></p>
                        <dl class="mt-4 space-y-1.5 border-t border-white/10 pt-4 text-sm">
                            <div class="flex justify-between"><dt class="text-white/70">Forfait {{ rate.label.toLowerCase() }}</dt><dd class="font-semibold">{{ money(rate.base) }} FCFA</dd></div>
                            <div class="flex justify-between"><dt class="text-white/70">{{ String(weight).replace('.', ',') }} kg × {{ money(rate.perKg) }}</dt><dd class="font-semibold">{{ money(rate.perKg * weight) }} FCFA</dd></div>
                        </dl>
                    </div>
                    <div class="mt-5">
                        <button type="button" class="btn-gold w-full" @click="goOffers">Trouver un transporteur</button>
                        <p class="mt-2 text-[11px] leading-snug text-white/60">Tarif confirmé au moment du dépôt. Contactez un point MOKILI TOUR ou un partenaire fret pour organiser l'enlèvement.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fret & logistique : offres des transporteurs -->
        <section id="offres" class="mt-14 scroll-mt-24">
            <h2 class="text-2xl font-extrabold text-navy-900">Offres des transporteurs</h2>
            <p class="mt-1 text-sm text-navy-600">National et international, par voie maritime, aérienne ou routière : documents, marchandises, véhicules, conteneurs.</p>

            <div class="mt-5 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5">
                <!-- Depart / arrivee -->
                <form class="flex flex-col gap-2 lg:flex-row lg:items-center" @submit.prevent="applyFilters">
                    <div class="flex flex-1 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                        <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 px-3 focus-within:border-gold-500">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 border-navy-700" />
                            <input v-model="f.origine" type="text" placeholder="Départ (ex. Brazzaville)" class="w-full border-0 py-3 text-sm focus:ring-0" />
                        </label>
                        <button type="button" class="mx-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-300 text-navy-700 hover:bg-slate-50" title="Inverser départ et arrivée" @click="swapCities">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 rotate-90 sm:rotate-0" fill="currentColor"><path d="M6.99 11 3 15l3.99 4v-3H14v-2H6.99v-3zM21 9l-3.99-4v3H10v2h7.01v3L21 9z" /></svg>
                        </button>
                        <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 px-3 focus-within:border-gold-500">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-gold-600" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z" /></svg>
                            <input v-model="f.destination" type="text" placeholder="Arrivée (ex. Douala, Guangzhou)" class="w-full border-0 py-3 text-sm focus:ring-0" />
                        </label>
                    </div>
                    <button type="submit" class="btn-gold lg:px-10">Rechercher</button>
                </form>

                <!-- Filtres -->
                <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-[auto_auto_1fr]">
                    <div>
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Zone</p>
                        <div class="inline-flex rounded-xl bg-slate-100 p-1">
                            <button
                                v-for="(label, key) in fret.scopes"
                                :key="key"
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition"
                                :class="f.scope === key ? 'bg-white text-navy-900 shadow' : 'text-slate-600 hover:text-navy-900'"
                                @click="toggle('scope', key)"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path :d="ICONS[key]" /></svg>
                                {{ label }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Transport</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(label, key) in fret.modes"
                                :key="key"
                                type="button"
                                class="flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition"
                                :class="f.mode === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 text-navy-800 hover:border-navy-300'"
                                @click="toggle('mode', key)"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" :class="f.mode === key ? 'text-gold-400' : 'text-teal-600'" fill="currentColor"><path :d="ICONS[key]" /></svg>
                                {{ label }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Type de colis</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(label, key) in fret.package_types"
                                :key="key"
                                type="button"
                                class="flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition"
                                :class="f.colis === key ? 'border-gold-600 bg-gold-500 text-navy-900' : 'border-slate-200 text-navy-800 hover:border-navy-300'"
                                @click="toggle('colis', key)"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" :class="f.colis === key ? 'text-navy-900' : 'text-navy-500'" fill="currentColor" :fill-rule="key === 'conteneur' ? 'evenodd' : 'nonzero'"><path :d="ICONS[key]" /></svg>
                                {{ label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-navy-900">{{ offers.total ?? offers.data.length }} offre{{ (offers.total ?? offers.data.length) > 1 ? 's' : '' }}</p>
                <button v-if="hasFilters" type="button" class="text-sm font-semibold text-navy-700 hover:underline" @click="resetFilters">Effacer les filtres</button>
            </div>

            <div class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="o in offers.data"
                    :key="o.id"
                    :href="`/fret/${o.slug}`"
                    class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg"
                >
                    <div class="relative">
                        <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} → {{ o.destination_city }}</ListingImage>
                        <span class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1" :class="MODE_STYLE[o.mode] ?? 'bg-white text-navy-900 ring-slate-200'">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path :d="ICONS[o.mode] ?? ICONS.route" /></svg>
                            {{ modeLabel[o.mode] ?? o.mode }}
                        </span>
                        <span class="absolute right-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white">{{ o.scope === 'national' ? 'National' : 'International' }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <p class="font-bold text-navy-900 group-hover:text-gold-700">{{ o.title }}</p>
                        <div class="mt-2 flex items-center gap-2 text-sm font-semibold text-navy-800">
                            <span class="truncate">{{ o.origin_city }}</span>
                            <span class="h-px flex-1 border-t border-dashed border-slate-300" />
                            <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-gold-600" fill="currentColor"><path :d="ICONS[o.mode] ?? ICONS.route" /></svg>
                            <span class="h-px flex-1 border-t border-dashed border-slate-300" />
                            <span class="truncate">{{ o.destination_city }}</span>
                        </div>
                        <p v-if="o.transit_time" class="mt-1 text-xs text-slate-500">Délai : {{ o.transit_time }}</p>
                        <div v-if="o.package_type_labels?.length" class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="p in o.package_type_labels" :key="p" class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ p }}</span>
                        </div>
                        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
                            <p class="text-lg font-extrabold text-navy-900">
                                {{ money(o.price_per_kg) }} <span class="text-xs font-semibold text-slate-500">{{ $cur(o.currency) }} / kg</span>
                            </p>
                            <span class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Voir</span>
                        </div>
                    </div>
                </Link>
            </div>
            <div v-if="!offers.data.length" class="mt-6 rounded-2xl border border-dashed border-slate-300 p-10 text-center">
                <p class="font-semibold text-navy-900">Aucune offre de fret pour ces critères.</p>
                <button v-if="hasFilters" type="button" class="mt-2 text-sm font-semibold text-gold-700 hover:underline" @click="resetFilters">Effacer les filtres</button>
            </div>
            <Paginator :page="offers" />
        </section>
    </div>
</template>
