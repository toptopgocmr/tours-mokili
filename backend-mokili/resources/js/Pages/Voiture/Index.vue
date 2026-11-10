<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import ListingImage from '@/Components/ListingImage.vue';
import GuideCover from '@/Components/GuideCover.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useCarShortlist, monthly } from '@/composables/useCarShortlist';

defineOptions({ layout: MainLayout });

// VOITURE : location facon Europcar / Sixt (lieu, dates + heures, retour
// different, age du conducteur), vente facon site d'annonces auto (filtres
// marque, prix, annee, kilometrage, carburant, boite + tri), mecanique.
const props = defineProps({
    vehicles: { type: Object, required: true },
    garages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    offerTypes: { type: Object, default: () => ({}) },
    facets: { type: Object, default: () => ({ brands: [], fuels: [], transmissions: [], categories: [] }) },
    sorts: { type: Object, default: () => ({}) },
    reservedIds: { type: Array, default: () => [] },
    finance: { type: Object, default: () => ({}) },
});
const { favorites, compare, isFav, inCompare, toggleFav, toggleCompare, clearCompare } = useCarShortlist();
const showFavs = () => router.get('/voiture', { type: 'vente', ids: favorites.value.join(',') || '0' }, { preserveScroll: true });
const onlyFavs = computed(() => !!props.filters.ids);

const f = props.filters;
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const todayIso = iso(new Date());
const plus = (n) => {
    const d = new Date();
    d.setDate(d.getDate() + n);
    return iso(d);
};

const s = reactive({
    type: f.type ?? '',
    q: f.q ?? '',
    city: f.city ?? '',
    vehicule: f.vehicule ?? 'voitures',
    start: f.start ?? '',
    end: f.end ?? '',
    start_time: f.start_time ?? '10:00',
    end_time: f.end_time ?? '10:00',
    return_elsewhere: !!f.return_city,
    return_city: f.return_city ?? '',
    age: f.age ?? '30+',
    brand: f.brand ?? '',
    model: f.model ?? '',
    color: f.color ?? '',
    condition: f.condition ?? '',
    doors: f.doors ?? '',
    first_owner: f.first_owner ? 1 : '',
    fuel: f.fuel ?? '',
    transmission: f.transmission ?? '',
    category: f.category ?? '',
    price_min: f.price_min ?? '',
    price_max: f.price_max ?? '',
    year_min: f.year_min ?? '',
    km_max: f.km_max ?? '',
    sort: f.sort ?? 'recent',
});

const query = () => {
    const keep = {
        location: ['type', 'city', 'vehicule', 'start', 'end', 'start_time', 'end_time', 'return_city', 'age', 'sort', 'transmission', 'category', 'price_max'],
        vente: ['type', 'q', 'city', 'brand', 'model', 'color', 'condition', 'doors', 'first_owner', 'fuel', 'transmission', 'category', 'price_min', 'price_max', 'year_min', 'km_max', 'sort'],
        mecanique: ['type', 'q', 'city'],
        '': ['type', 'q', 'city'],
    }[s.type] ?? ['type'];
    const out = {};
    keep.forEach((k) => {
        if (k === 'return_city' && !s.return_elsewhere) return;
        if (s[k] !== '' && s[k] !== null && !(k === 'sort' && s[k] === 'recent')) out[k] = s[k];
    });
    return out;
};
const submit = () => router.get('/voiture', query(), { preserveState: true, preserveScroll: true, replace: true });
const setType = (t) => {
    s.type = t;
    if (t === 'location' && !s.start) {
        s.start = plus(1);
        s.end = plus(4);
    }
    router.get('/voiture', query(), { preserveScroll: true, replace: true });
};
const resetVente = () => {
    Object.assign(s, { q: '', brand: '', model: '', color: '', condition: '', doors: '', first_owner: '', fuel: '', transmission: '', category: '', price_min: '', price_max: '', year_min: '', km_max: '' });
    submit();
};

const money = (x) => Number(x ?? 0).toLocaleString('fr-FR');
const cap = (x) => (x === 'suv' ? 'SUV' : x ? String(x).charAt(0).toUpperCase() + String(x).slice(1) : '');
const days = computed(() => {
    if (!s.start || !s.end) return 0;
    return Math.max(1, Math.round((new Date(s.end) - new Date(s.start)) / 86400000));
});
const detailHref = (v) => {
    if (v.offer_type !== 'location' || !s.start || !s.end) return `/voiture/${v.slug}`;
    const p = new URLSearchParams({ start: s.start, end: s.end, start_time: s.start_time, end_time: s.end_time, age: s.age });
    if (s.return_elsewhere && s.return_city) p.set('return_city', s.return_city);
    return `/voiture/${v.slug}?${p.toString()}`;
};
const datesSearched = computed(() => s.type === 'location' && f.start && f.end);
const filtersOpen = ref(false);
const activeVente = computed(() => ['q', 'brand', 'model', 'color', 'condition', 'doors', 'first_owner', 'fuel', 'transmission', 'category', 'price_min', 'price_max', 'year_min', 'km_max'].filter((k) => f[k]).length);
const cat = computed(() => usePage().props.catalogue?.voiture ?? {});
const models = computed(() => cat.value.brands?.[s.brand] ?? []);
const countOf = (list, key) => (props.facets[list] ?? []).find((x) => x.key === key)?.count ?? 0;
const label = (group, key) => cat.value[group]?.[key] ?? cap(key);
const colorOf = (key) => cat.value.colors?.[key];

const HERO = {
    '': ['Location, vente et mecanique', 'Louez, achetez ou faites entretenir votre vehicule.'],
    location: ['Location de voitures et utilitaires', 'Citadines, SUV, berlines et utilitaires, avec ou sans chauffeur.'],
    vente: ['Voitures a vendre', 'Annonces de particuliers et de professionnels au Congo et en Afrique centrale.'],
    mecanique: ['Mecanique & entretien', 'Garages, vidange, diagnostic, carrosserie, depannage.'],
};
const PRICE_STEPS = [2000000, 5000000, 10000000, 20000000];
const YEARS = Array.from({ length: 16 }, (_, i) => new Date().getFullYear() - i);
</script>

<template>
    <Head :title="HERO[s.type]?.[0] ?? 'Voiture'" />

    <PhotoHero class="px-4 pb-24 pt-10 text-white sm:pt-12">
        <div class="mx-auto max-w-6xl">
            <h1 class="text-3xl font-extrabold drop-shadow sm:text-4xl">{{ HERO[s.type]?.[0] }}</h1>
            <p class="mt-1 text-white/90 drop-shadow">{{ HERO[s.type]?.[1] }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="!s.type ? 'bg-white text-navy-900' : 'bg-white/15 text-white hover:bg-white/25'" @click="setType('')">Tout</button>
                <button
                    v-for="(label, key) in offerTypes"
                    :key="key"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    :class="s.type === key ? 'bg-white text-navy-900' : 'bg-white/15 text-white hover:bg-white/25'"
                    @click="setType(key)"
                >{{ label }}</button>
            </div>
        </div>
    </PhotoHero>

    <div class="relative z-10 -mt-20 px-4">
        <!-- ===== LOCATION : formulaire facon Europcar / Sixt ===== -->
        <form v-if="s.type === 'location'" class="mx-auto max-w-6xl rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgba(0,0,0,0.18)] sm:p-6" @submit.prevent="submit">
            <p class="text-sm font-bold text-slate-900">Quel type de vehicule ?</p>
            <div class="mt-2 inline-flex overflow-hidden rounded-xl border border-slate-300">
                <button v-for="[k, label] in [['voitures', 'Voitures'], ['utilitaires', 'Utilitaires']]" :key="k" type="button" class="px-5 py-2.5 text-sm font-semibold transition" :class="s.vehicule === k ? 'bg-navy-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'" @click="s.vehicule = k">{{ label }}</button>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-900">Lieu de retrait</p>
                        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600">
                            <input v-model="s.return_elsewhere" type="checkbox" class="rounded text-navy-900" /> Retour dans un autre lieu
                        </label>
                    </div>
                    <input v-model="s.city" type="text" placeholder="Ville, aeroport, adresse..." class="mt-2 h-12 w-full rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500" />
                    <input v-if="s.return_elsewhere" v-model="s.return_city" type="text" placeholder="Lieu de retour" class="mt-2 h-12 w-full rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500" />
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Date et heure de depart</p>
                    <div class="mt-2 flex h-12 overflow-hidden rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-gold-500">
                        <input v-model="s.start" type="date" :min="todayIso" class="min-w-0 flex-1 border-0 text-sm focus:ring-0" @change="s.end < s.start && (s.end = s.start)" />
                        <input v-model="s.start_time" type="time" step="1800" class="w-28 border-0 border-l border-slate-200 text-sm focus:ring-0" />
                    </div>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Date et heure de retour</p>
                    <div class="mt-2 flex h-12 overflow-hidden rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-gold-500">
                        <input v-model="s.end" type="date" :min="s.start || todayIso" class="min-w-0 flex-1 border-0 text-sm focus:ring-0" />
                        <input v-model="s.end_time" type="time" step="1800" class="w-28 border-0 border-l border-slate-200 text-sm focus:ring-0" />
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-5 text-sm text-slate-700">
                    <label class="flex items-center gap-2">Age du conducteur
                        <select v-model="s.age" class="rounded-lg border-slate-300 py-1.5 text-sm font-semibold">
                            <option value="18-24">18 - 24 ans</option>
                            <option value="25-29">25 - 29 ans</option>
                            <option value="30+">30 ans et plus</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2">Boite
                        <select v-model="s.transmission" class="rounded-lg border-slate-300 py-1.5 text-sm font-semibold">
                            <option value="">Toutes</option>
                            <option value="automatique">Automatique</option>
                            <option value="manuelle">Manuelle</option>
                        </select>
                    </label>
                    <span v-if="days" class="text-slate-500">{{ days }} jour(s) de location</span>
                </div>
                <button type="submit" class="h-12 rounded-xl bg-gold-500 px-10 font-bold text-navy-900 transition hover:bg-gold-400">Voir les vehicules</button>
            </div>
        </form>

        <!-- ===== VENTE : recherche facon La Centrale ===== -->
        <form v-else-if="s.type === 'vente'" class="mx-auto max-w-6xl rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgba(0,0,0,0.18)]" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block text-xs font-semibold text-slate-500">Marque
                    <select v-model="s.brand" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900" @change="s.model = ''">
                        <option value="">Toutes les marques</option>
                        <option v-for="(m, b) in cat.brands" :key="b" :value="b">{{ b }}</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Modele
                    <select v-model="s.model" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 disabled:bg-slate-50" :disabled="!models.length">
                        <option value="">{{ s.brand ? 'Tous les modeles' : 'Choisir une marque' }}</option>
                        <option v-for="m in models" :key="m" :value="m">{{ m }}</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Carburant
                    <select v-model="s.fuel" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900">
                        <option value="">Tous les carburants</option>
                        <option v-for="(lbl, key) in cat.fuels" :key="key" :value="key">{{ lbl }}</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Boite de vitesses
                    <select v-model="s.transmission" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900">
                        <option value="">Automatique ou manuelle</option>
                        <option value="automatique">Automatique</option>
                        <option value="manuelle">Manuelle</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Prix maximum
                    <select v-model="s.price_max" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900">
                        <option value="">Sans limite</option>
                        <option v-for="p in [1000000, 2000000, 3000000, 5000000, 7500000, 10000000, 15000000, 20000000, 30000000, 50000000, 100000000]" :key="p" :value="p">{{ money(p) }} XAF</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Annee minimum
                    <select v-model="s.year_min" class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900">
                        <option value="">Toutes les annees</option>
                        <option v-for="y in YEARS" :key="y" :value="y">{{ y }} et plus</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-500">Ville
                    <input v-model="s.city" type="text" placeholder="Brazzaville, Pointe-Noire..." class="mt-1 h-12 w-full rounded-xl border-slate-300 text-sm" />
                </label>
                <div class="flex items-end">
                    <button type="submit" class="h-12 w-full rounded-xl bg-gold-500 px-8 font-bold text-navy-900 transition hover:bg-gold-400">Rechercher<span v-if="vehicles.total !== undefined"> ({{ vehicles.total }})</span></button>
                </div>
            </div>
        </form>

        <!-- ===== TOUT / MECANIQUE : barre de recherche ===== -->
        <form v-else class="mx-auto flex max-w-6xl flex-col gap-2 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(0,0,0,0.18)] sm:flex-row" @submit.prevent="submit">
            <input v-model="s.q" type="text" :placeholder="s.type === 'mecanique' ? 'Prestation, garage (ex. vidange)...' : 'Marque, modele (ex. Toyota RAV4)...'" class="h-12 flex-1 rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500" />
            <input v-model="s.city" type="text" placeholder="Ville" class="h-12 rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500 sm:w-52" />
            <button type="submit" class="h-12 rounded-xl bg-gold-500 px-10 font-bold text-navy-900 transition hover:bg-gold-400">Rechercher</button>
        </form>
    </div>

    <!-- Avantages -->
    <div v-if="s.type === 'location' || s.type === 'vente'" class="mx-auto mt-8 grid max-w-6xl gap-4 px-4 sm:grid-cols-3">
        <template v-if="s.type === 'location'">
            <div v-for="[t, d] in [['Disponibilite en direct', 'Seuls les vehicules libres sur vos dates sont affiches.'], ['Paiement Mobile Money', 'Reservez et payez en quelques secondes.'], ['Annulation flexible', 'Gratuite jusqu\'a 24 h avant la prise en charge.']]" :key="t" class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-900 text-sm text-gold-400">✓</span>
                <div><p class="font-bold text-slate-900">{{ t }}</p><p class="text-sm text-slate-500">{{ d }}</p></div>
            </div>
        </template>
        <template v-else>
            <div v-for="[t, d] in [['Annonces verifiees', 'Chaque annonce est controlee avant publication.'], ['Contact direct', 'Appelez ou ecrivez au vendeur sur WhatsApp.'], ['Conseils d\'achat', 'Verifiez la carte grise et essayez le vehicule.']]" :key="t" class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-900 text-sm text-gold-400">✓</span>
                <div><p class="font-bold text-slate-900">{{ t }}</p><p class="text-sm text-slate-500">{{ d }}</p></div>
            </div>
        </template>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <!-- Garages (mecanique) -->
        <section v-if="garages.length" class="mb-10">
            <h2 class="text-xl font-bold text-navy-900">Garages partenaires</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="g in garages" :key="g.id" :href="`/partenaires/${g.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                    <div class="h-28"><GuideCover :src="g.image_url" category="garage" :alt="g.name" icon-class="h-10 w-10" /></div>
                    <div class="p-3"><p class="font-semibold text-navy-900">{{ g.name }}</p><p class="text-xs text-navy-500">{{ g.district ?? g.city }}</p></div>
                </Link>
            </div>
        </section>

        <!-- ===== VENTE : sidebar de filtres + annonces ===== -->
        <div v-if="s.type === 'vente'" class="grid gap-6 lg:grid-cols-[270px_1fr]">
            <aside :class="filtersOpen ? 'block' : 'hidden lg:block'">
                <div class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="font-bold text-slate-900">Filtres</p>
                        <button v-if="activeVente" type="button" class="text-xs font-semibold text-[#006ce4]" @click="resetVente">Tout effacer</button>
                    </div>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Marque</span>
                        <select v-model="s.brand" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="s.model = ''; submit()">
                            <option value="">Toutes les marques</option>
                            <option v-for="(m, b) in cat.brands" :key="b" :value="b">{{ b }}{{ countOf('brands', b) ? ` (${countOf('brands', b)})` : '' }}</option>
                        </select>
                    </label>
                    <label v-if="models.length" class="block text-sm">
                        <span class="font-semibold text-slate-800">Modele</span>
                        <select v-model="s.model" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Tous les modeles</option>
                            <option v-for="m in models" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </label>
                    <div class="text-sm">
                        <span class="font-semibold text-slate-800">Prix (XAF)</span>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <input v-model.number="s.price_min" type="number" min="0" placeholder="Min" class="rounded-lg border-slate-300 text-sm" @change="submit" />
                            <input v-model.number="s.price_max" type="number" min="0" placeholder="Max" class="rounded-lg border-slate-300 text-sm" @change="submit" />
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button v-for="p in PRICE_STEPS" :key="p" type="button" class="rounded-full border px-2.5 py-1 text-xs" :class="Number(s.price_max) === p ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 text-slate-600'" @click="s.price_min = ''; s.price_max = p; submit()">≤ {{ (p / 1000000).toLocaleString('fr-FR') }} M</button>
                        </div>
                    </div>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Annee minimum</span>
                        <select v-model="s.year_min" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Toutes</option>
                            <option v-for="y in YEARS" :key="y" :value="y">{{ y }} et plus</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Kilometrage maximum</span>
                        <select v-model="s.km_max" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Peu importe</option>
                            <option v-for="k in [20000, 50000, 100000, 150000, 200000]" :key="k" :value="k">{{ money(k) }} km</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Boite de vitesses</span>
                        <select v-model="s.transmission" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Automatique ou manuelle</option>
                            <option value="automatique">Automatique</option>
                            <option value="manuelle">Manuelle</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Carburant</span>
                        <select v-model="s.fuel" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Tous les carburants</option>
                            <option v-for="(lbl, key) in cat.fuels" :key="key" :value="key">{{ lbl }}{{ countOf('fuels', key) ? ` (${countOf('fuels', key)})` : '' }}</option>
                        </select>
                    </label>
                    <div class="text-sm">
                        <span class="font-semibold text-slate-800">Couleur</span>
                        <div class="mt-2 grid grid-cols-6 gap-2">
                            <button v-for="(c, key) in cat.colors" :key="key" type="button" :title="c[0]" class="h-8 w-8 rounded-full ring-1 ring-black/15 transition" :class="s.color === key ? 'ring-2 ring-offset-2 ring-navy-900' : 'hover:scale-110'" :style="{ background: c[1] }" @click="s.color = s.color === key ? '' : key; submit()" />
                        </div>
                        <p v-if="s.color" class="mt-1 text-xs text-slate-500">{{ colorOf(s.color)?.[0] }}</p>
                    </div>
                    <div class="text-sm">
                        <span class="font-semibold text-slate-800">Etat</span>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button v-for="(lbl, key) in cat.conditions" :key="key" type="button" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="s.condition === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 text-slate-700'" @click="s.condition = s.condition === key ? '' : key; submit()">{{ lbl }}</button>
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-slate-700">
                            <input type="checkbox" :checked="!!s.first_owner" class="rounded text-navy-900" @change="s.first_owner = s.first_owner ? '' : 1; submit()" /> Premiere main uniquement
                        </label>
                    </div>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-800">Portes</span>
                        <select v-model="s.doors" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="submit">
                            <option value="">Peu importe</option>
                            <option v-for="n in [2, 3, 4, 5]" :key="n" :value="n">{{ n }} portes</option>
                        </select>
                    </label>
                    <div class="text-sm">
                        <span class="font-semibold text-slate-800">Type de carrosserie</span>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button v-for="(lbl, key) in cat.categories" :key="key" type="button" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="s.category === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 text-slate-700'" @click="s.category = s.category === key ? '' : key; submit()">{{ lbl }}</button>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-extrabold text-slate-900">{{ onlyFavs ? 'Mes favoris' : `${vehicles.total ?? vehicles.data.length} annonce(s)` }}</h2>
                    <div class="flex items-center gap-2">
                        <button v-if="onlyFavs" type="button" class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold" @click="setType('vente')">Toutes les annonces</button>
                        <button v-else type="button" class="flex items-center gap-1.5 rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold" :disabled="!favorites.length" :class="!favorites.length && 'opacity-50'" @click="showFavs">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="#f43f5e"><path d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.6 3.3 5 6.9 5c2 0 3.4 1.1 4.1 2.2C11.7 6.1 13.1 5 15.1 5c3.6 0 5.7 3.6 4.4 6.8C19.5 16.4 12 21 12 21z" /></svg>
                            Favoris ({{ favorites.length }})
                        </button>
                        <button type="button" class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold lg:hidden" @click="filtersOpen = !filtersOpen">Filtres<span v-if="activeVente"> ({{ activeVente }})</span></button>
                        <select v-model="s.sort" class="rounded-full border-slate-300 py-1.5 pl-3 pr-8 text-sm font-semibold" @change="submit">
                            <option v-for="(label, key) in sorts" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <article v-for="v in vehicles.data" :key="v.id" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-xl">
                        <div class="relative">
                            <Link :href="detailHref(v)"><ListingImage :src="v.image_url" fallback-class="bg-navy-800" class="h-48">{{ v.brand }} {{ v.model }}</ListingImage></Link>
                            <div class="absolute right-2 top-2 flex items-center gap-1 rounded-full bg-black/55 px-1.5 py-1 text-xs font-semibold text-white backdrop-blur">
                                <button type="button" class="rounded-full px-2 py-0.5 underline-offset-2 hover:underline" :class="inCompare(v.id) && 'bg-white text-navy-900 no-underline'" @click="toggleCompare(v.id)">{{ inCompare(v.id) ? '✓ Compare' : 'Comparer' }}</button>
                                <span class="h-3 w-px bg-white/50" />
                                <button type="button" class="flex h-6 w-6 items-center justify-center rounded-full" :aria-label="isFav(v.id) ? 'Retirer des favoris' : 'Ajouter aux favoris'" @click="toggleFav(v.id)">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" :fill="isFav(v.id) ? '#f43f5e' : 'none'" :stroke="isFav(v.id) ? '#f43f5e' : 'currentColor'" stroke-width="2"><path d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.6 3.3 5 6.9 5c2 0 3.4 1.1 4.1 2.2C11.7 6.1 13.1 5 15.1 5c3.6 0 5.7 3.6 4.4 6.8C19.5 16.4 12 21 12 21z" /></svg>
                                </button>
                            </div>
                            <span v-if="reservedIds.includes(v.id)" class="absolute left-2 top-2 rounded-md bg-red-600 px-2 py-1 text-[11px] font-bold uppercase text-white">Reserve</span>
                            <PromoBadge v-else :promo="v.promo" class="absolute left-2 top-2" />
                        </div>
                        <div v-if="v.warranty_months" class="flex items-center gap-2 bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Garantie {{ v.warranty_months }} mois</div>
                        <div v-else-if="v.condition === 'neuf' || v.first_owner" class="flex items-center gap-2 bg-gold-500 px-4 py-2 text-sm font-semibold text-navy-900">{{ v.condition === 'neuf' ? 'Vehicule neuf' : 'Premiere main' }}</div>
                        <Link :href="detailHref(v)" class="flex flex-1 flex-col p-4">
                            <p class="text-lg font-bold leading-tight text-slate-900">{{ v.brand }} {{ v.model }}</p>
                            <p class="mt-0.5 truncate text-xs font-medium uppercase tracking-wide text-slate-500">{{ v.version || v.title }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5 text-xs font-medium text-slate-700">
                                <span v-if="v.mileage_km" class="rounded-full bg-slate-100 px-2.5 py-1">{{ money(v.mileage_km) }} km</span>
                                <span v-if="v.fuel" class="rounded-full bg-slate-100 px-2.5 py-1">{{ label('fuels', v.fuel) }}</span>
                                <span v-if="v.year" class="rounded-full bg-slate-100 px-2.5 py-1">{{ v.year }}</span>
                                <span v-if="v.transmission" class="rounded-full bg-slate-100 px-2.5 py-1">{{ cap(v.transmission) }}</span>
                                <span v-if="v.color" class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1"><span class="h-2.5 w-2.5 rounded-full ring-1 ring-black/20" :style="{ background: colorOf(v.color)?.[1] }" />{{ colorOf(v.color)?.[0] ?? cap(v.color) }}</span>
                            </div>
                            <p v-if="v.options?.length" class="mt-2 truncate text-xs text-slate-500">{{ v.options.slice(0, 4).join(' · ') }}</p>
                            <div class="mt-auto border-t border-slate-100 pt-3">
                                <p v-if="v.display_price?.original" class="text-sm text-red-500 line-through">{{ money(v.display_price.original) }} {{ v.currency }}</p>
                                <p class="text-2xl font-extrabold text-navy-900">{{ v.display_price ? `${money(v.display_price.amount)} ${v.currency}` : 'Prix sur demande' }}</p>
                                <p v-if="v.display_price" class="text-xs text-slate-500">ou environ <b class="text-sm text-navy-900">{{ money(monthly(v.display_price.amount, finance)) }} {{ v.currency }}</b> /mois*</p>
                                <p class="mt-1 text-xs text-slate-400">{{ v.city }}</p>
                            </div>
                        </Link>
                    </article>
                </div>
                <p v-if="vehicles.data.length" class="mt-4 text-[11px] text-slate-400">* Estimation indicative avec {{ finance.down_payment_percent }} % d'apport sur {{ finance.months }} mois a {{ finance.annual_rate }} % par an. Un credit vous engage et doit etre rembourse.</p>
                <div v-if="!vehicles.data.length" class="mt-6 rounded-2xl border bg-white p-10 text-center">
                    <p class="text-lg font-semibold text-slate-900">Aucune annonce ne correspond a vos criteres.</p>
                    <button v-if="activeVente" type="button" class="mt-3 font-semibold text-[#006ce4]" @click="resetVente">Retirer les filtres</button>
                </div>
                <Paginator :page="vehicles" />
            </div>
        </div>

        <!-- ===== LOCATION / TOUT / MECANIQUE : cartes ===== -->
        <template v-else>
            <div v-if="s.type === 'location'" class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ vehicles.total ?? vehicles.data.length }} vehicule(s){{ datesSearched ? ' disponibles sur vos dates' : '' }}
                </h2>
                <select v-model="s.sort" class="rounded-full border-slate-300 py-1.5 pl-3 pr-8 text-sm font-semibold" @change="submit">
                    <option value="recent">Recommandes</option>
                    <option value="prix_asc">Prix par jour croissant</option>
                    <option value="prix_desc">Prix par jour decroissant</option>
                </select>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="v in vehicles.data" :key="v.id" :href="detailHref(v)" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg">
                    <div class="relative">
                        <ListingImage :src="v.image_url" fallback-class="bg-navy-800" class="h-44">{{ v.brand }} {{ v.model }}</ListingImage>
                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-0.5 text-xs font-semibold text-navy-900">{{ v.offer_type_label }}</span>
                        <PromoBadge :promo="v.promo" class="absolute right-3 top-3" />
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <p class="font-bold text-slate-900">{{ v.title }}</p>
                        <p class="text-xs text-slate-500">{{ cap(v.category) || 'Vehicule' }} · {{ v.city }}</p>
                        <div v-if="v.offer_type !== 'mecanique'" class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                            <span v-if="v.seats">{{ v.seats }} places</span>
                            <span v-if="v.transmission">{{ cap(v.transmission) }}</span>
                            <span v-if="v.fuel">{{ cap(v.fuel) }}</span>
                            <span v-if="v.mileage_km">{{ money(v.mileage_km) }} km</span>
                        </div>
                        <div class="mt-auto flex items-end justify-between pt-4">
                            <div>
                                <p v-if="v.display_price?.original" class="text-xs text-red-500 line-through">{{ money(v.display_price.original) }}</p>
                                <p class="text-lg font-extrabold text-navy-900">
                                    {{ v.display_price ? money(v.display_price.amount) : 'Sur demande' }}<span v-if="v.display_price" class="text-sm font-semibold"> {{ v.currency }}</span>
                                    <span v-if="v.offer_type === 'location'" class="text-xs font-normal text-slate-500">/ jour</span>
                                </p>
                                <p v-if="v.offer_type === 'location' && datesSearched && v.display_price" class="text-xs text-slate-500">{{ money(v.display_price.amount * days) }} {{ v.currency }} pour {{ days }} jour(s)</p>
                            </div>
                            <span class="rounded-lg bg-navy-900 px-3 py-2 text-xs font-semibold text-white group-hover:bg-navy-800">{{ v.offer_type === 'location' ? 'Reserver' : 'Voir' }}</span>
                        </div>
                        <p v-if="v.offer_type === 'location' && datesSearched" class="mt-2 text-xs font-semibold text-green-700">✓ Disponible sur vos dates</p>
                    </div>
                </Link>
            </div>
            <p v-if="!vehicles.data.length" class="mt-10 text-center text-navy-500">
                {{ datesSearched ? 'Aucun vehicule libre sur ces dates : essayez d\'autres dates.' : 'Aucune annonce pour le moment dans cette categorie.' }}
            </p>
            <Paginator :page="vehicles" />
        </template>

        <!-- Barre du comparateur -->
        <div v-if="s.type === 'vente' && compare.length" class="fixed inset-x-0 bottom-4 z-40 flex justify-center px-4">
            <div class="flex items-center gap-3 rounded-full bg-navy-900 py-2 pl-5 pr-2 text-sm text-white shadow-2xl">
                <span>{{ compare.length }} vehicule(s) a comparer <span class="text-white/60">(4 max)</span></span>
                <button type="button" class="text-xs text-white/70 underline" @click="clearCompare">Vider</button>
                <Link :href="`/voiture/comparer?ids=${compare.join(',')}`" class="rounded-full bg-gold-500 px-4 py-2 font-bold text-navy-900" :class="compare.length < 2 && 'pointer-events-none opacity-50'">Comparer</Link>
            </div>
        </div>

        <PartnersSection v-if="s.type !== 'mecanique' && s.type !== 'vente'" module="voiture" title="Taxis, VTC & garages" subtitle="Applications de taxi, chauffeurs prives et garages a Brazzaville." />
    </div>
</template>
