<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import ListingImage from '@/Components/ListingImage.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

// Recherche d'hebergement facon Booking.com : barre jaune (destination,
// dates, occupants), filtres avec compteurs, tri, cartes horizontales.
const props = defineProps({
    listings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    propertyTypes: { type: Object, default: () => ({}) },
    facets: { type: Object, default: () => ({ types: [], amenities: [], price_max: 0 }) },
    sorts: { type: Object, default: () => ({}) },
    popularCities: { type: Array, default: () => [] },
});

const user = computed(() => usePage().props.auth?.user);
const firstName = computed(() => (user.value?.name ?? '').split(' ').slice(0, 2).join(' '));
const asArray = (v) => (Array.isArray(v) ? v : v ? [v] : []);
const today = new Date().toISOString().slice(0, 10);

const s = reactive({
    city: props.filters.city ?? '',
    check_in: props.filters.check_in ?? '',
    check_out: props.filters.check_out ?? '',
    adults: Number(props.filters.adults ?? props.filters.guests ?? 2),
    children: Number(props.filters.children ?? 0),
    rooms: Number(props.filters.rooms ?? 1),
    price_min: props.filters.price_min ?? '',
    price_max: props.filters.price_max ?? '',
    type: asArray(props.filters.type),
    bedrooms: props.filters.bedrooms ?? '',
    amenities: asArray(props.filters.amenities),
    sort: props.filters.sort ?? 'populaires',
});

const hasSearch = computed(() => !!(props.filters.city || props.filters.check_in || props.filters.type || props.filters.amenities || props.filters.price_max));

const query = () => {
    const q = {};
    for (const [k, v] of Object.entries(s)) {
        if (Array.isArray(v) ? v.length : v !== '' && v !== null) q[k] = v;
    }
    if (q.sort === 'populaires') delete q.sort;
    if (q.children === 0) delete q.children;
    if (q.rooms === 1) delete q.rooms;
    return q;
};
const submit = () => router.get('/logement', query(), { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => {
    Object.assign(s, { price_min: '', price_max: '', type: [], bedrooms: '', amenities: [] });
    submit();
};
const toggle = (list, key) => {
    const i = s[list].indexOf(key);
    i >= 0 ? s[list].splice(i, 1) : s[list].push(key);
    submit();
};
const goCity = (city) => {
    s.city = city;
    submit();
};

// Dates
const fmt = (d) => (d ? new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) : '');
const nights = computed(() => {
    if (!s.check_in || !s.check_out) return 0;
    const n = Math.round((new Date(s.check_out) - new Date(s.check_in)) / 86400000);
    return n > 0 ? n : 0;
});
const onCheckIn = () => {
    if (s.check_out && s.check_out <= s.check_in) {
        const d = new Date(s.check_in);
        d.setDate(d.getDate() + 1);
        s.check_out = d.toISOString().slice(0, 10);
    }
};

// Occupants (popover)
const occOpen = ref(false);
const occRef = ref(null);
const occLabel = computed(() => `${s.adults} adulte${s.adults > 1 ? 's' : ''} · ${s.children} enfant${s.children > 1 ? 's' : ''} · ${s.rooms} chambre${s.rooms > 1 ? 's' : ''}`);
const step = (k, d, min, max) => (s[k] = Math.min(max, Math.max(min, s[k] + d)));
const closeOcc = (e) => {
    if (occRef.value && !occRef.value.contains(e.target)) occOpen.value = false;
};
onMounted(() => document.addEventListener('click', closeOcc));
onBeforeUnmount(() => document.removeEventListener('click', closeOcc));

// Cartes
const money = (v) => Number(v ?? 0).toLocaleString('fr-FR');
const priceFor = (l) => Number(l.final_price_per_night ?? l.price_per_night) * Math.max(1, nights.value);
const AMENITY_ICONS = { wifi: '📶', climatisation: '❄️', parking: '🅿️', piscine: '🏊', 'petit-dejeuner': '🥐', 'salle de sport': '🏋️', jardin: '🌳', ascenseur: '🛗', cuisine: '🍳', television: '📺', navette: '🚐' };
const AMENITY_LABELS = computed(() => Object.fromEntries(props.facets.amenities.map((a) => [a.key, a.label])));
const amenityLabel = (a) => AMENITY_LABELS.value[a] ?? a;
const TYPE_ICONS = { villa: '🏡', hotel: '🏨', appartement: '🏢' };

const href = (l) => {
    const p = new URLSearchParams();
    ['check_in', 'check_out'].forEach((k) => s[k] && p.set(k, s[k]));
    p.set('adults', s.adults);
    if (s.children) p.set('children', s.children);
    if (s.rooms > 1) p.set('rooms', s.rooms);
    return `/logement/${l.slug}?${p.toString()}`;
};
const filtersOpen = ref(false);
const activeFilters = computed(() => s.type.length + s.amenities.length + (s.price_max ? 1 : 0) + (s.bedrooms ? 1 : 0));
</script>

<template>
    <Head title="Hotels, appartements et villas" />

    <!-- En-tete bleu + barre de recherche jaune -->
    <PhotoHero class="px-4 pb-16 pt-12 text-white sm:pt-16">
        <div class="mx-auto max-w-6xl">
            <h1 class="text-3xl font-extrabold leading-tight sm:text-5xl">
                <template v-if="firstName">{{ firstName }}, quelle sera votre prochaine destination ?</template>
                <template v-else>Trouvez votre prochain sejour</template>
            </h1>
            <p class="mt-3 text-lg text-white/90 sm:text-xl">Hotels, appartements et villas au Congo, en Afrique et dans le monde. Paiement par Mobile Money.</p>
        </div>
    </PhotoHero>

    <div class="relative z-20 -mt-8 px-4">
        <form class="mx-auto grid max-w-6xl gap-1 rounded-xl bg-[#ffb700] p-1 shadow-lg lg:grid-cols-[1.3fr_1.2fr_1.2fr_auto]" @submit.prevent="submit">
            <label class="flex items-center gap-3 rounded-lg bg-white px-3 py-2.5">
                                <span class="min-w-0 flex-1">
                    <span class="block text-[11px] text-slate-500">Indiquez la destination</span>
                    <input v-model="s.city" type="text" placeholder="Ville, quartier, hotel..." class="w-full border-0 p-0 text-sm font-semibold text-slate-900 placeholder:font-normal focus:ring-0" />
                </span>
                <button v-if="s.city" type="button" class="text-slate-400 hover:text-slate-700" aria-label="Effacer" @click="s.city = ''">✕</button>
            </label>

            <div class="flex items-center gap-3 rounded-lg bg-white px-3 py-2.5">
                                <div class="grid flex-1 grid-cols-2 gap-2">
                    <label class="min-w-0">
                        <span class="block text-[11px] text-slate-500">Arrivee</span>
                        <input v-model="s.check_in" type="date" :min="today" class="w-full border-0 p-0 text-sm font-semibold text-slate-900 focus:ring-0" @change="onCheckIn" />
                    </label>
                    <label class="min-w-0 border-l pl-2">
                        <span class="block text-[11px] text-slate-500">Depart</span>
                        <input v-model="s.check_out" type="date" :min="s.check_in || today" class="w-full border-0 p-0 text-sm font-semibold text-slate-900 focus:ring-0" />
                    </label>
                </div>
            </div>

            <div ref="occRef" class="relative">
                <button type="button" class="flex h-full w-full items-center gap-3 rounded-lg bg-white px-3 py-2.5 text-left" @click.stop="occOpen = !occOpen">
                                        <span class="min-w-0 flex-1">
                        <span class="block text-[11px] text-slate-500">Voyageurs et chambres</span>
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ occLabel }}</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div v-if="occOpen" class="absolute right-0 top-full z-30 mt-2 w-full min-w-[280px] rounded-xl border bg-white p-4 text-slate-900 shadow-xl" @click.stop>
                    <div v-for="row in [['adults', 'Adultes', 1, 30], ['children', 'Enfants', 0, 10], ['rooms', 'Chambres', 1, 10]]" :key="row[0]" class="flex items-center justify-between py-2">
                        <span class="text-sm font-medium">{{ row[1] }}</span>
                        <div class="flex items-center overflow-hidden rounded-md border">
                            <button type="button" class="px-3 py-1.5 text-lg text-[#006ce4] disabled:text-slate-300" :disabled="s[row[0]] <= row[2]" @click="step(row[0], -1, row[2], row[3])">−</button>
                            <span class="w-8 text-center text-sm font-semibold">{{ s[row[0]] }}</span>
                            <button type="button" class="px-3 py-1.5 text-lg text-[#006ce4] disabled:text-slate-300" :disabled="s[row[0]] >= row[3]" @click="step(row[0], 1, row[2], row[3])">+</button>
                        </div>
                    </div>
                    <button type="button" class="mt-2 w-full rounded-md border border-[#006ce4] py-2 text-sm font-semibold text-[#006ce4] hover:bg-blue-50" @click="occOpen = false">OK</button>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-[#006ce4] px-8 py-3 text-lg font-semibold text-white transition hover:bg-[#0057b8]">Rechercher</button>
        </form>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <!-- Types d'hebergement -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="t in facets.types"
                :key="t.key"
                type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                :class="s.type.includes(t.key) ? 'border-[#006ce4] bg-blue-50 text-[#006ce4]' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-500'"
                @click="toggle('type', t.key)"
            >
                {{ t.label }} <span class="font-normal text-slate-500">({{ t.count }})</span>
            </button>
        </div>

        <!-- Destinations en vogue -->
        <section v-if="!hasSearch && popularCities.length" class="mt-8">
            <h2 class="text-2xl font-extrabold text-slate-900">Destinations en vogue</h2>
            <p class="text-sm text-slate-500">Les villes les plus demandees par les voyageurs MOKILI TOUR</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="(c, i) in popularCities"
                    :key="c.city"
                    type="button"
                    class="group relative overflow-hidden rounded-xl text-left"
                    :class="i < 2 ? 'h-56 lg:col-span-1' : 'h-44'"
                    @click="goCity(c.city)"
                >
                    <img v-if="c.image" :src="c.image" :alt="c.city" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div v-else class="h-full w-full bg-gradient-to-br from-[#003b95] to-[#006ce4]" />
                    <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/10 to-transparent" />
                    <div class="absolute left-4 top-3 text-white">
                        <p class="text-2xl font-extrabold drop-shadow">{{ c.city }}</p>
                        <p class="text-sm drop-shadow">{{ c.total }} hebergement(s) · des {{ money(c.from_price) }} XAF</p>
                    </div>
                </button>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[260px_1fr]">
            <!-- Filtres -->
            <aside :class="filtersOpen ? 'block' : 'hidden lg:block'">
                <div class="rounded-xl border bg-white">
                    <div class="flex items-center justify-between border-b px-4 py-3">
                        <p class="font-bold text-slate-900">Filtrer par :</p>
                        <button v-if="activeFilters" type="button" class="text-xs font-semibold text-[#006ce4]" @click="resetFilters">Tout effacer</button>
                    </div>
                    <div class="border-b px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Votre budget (par nuit)</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <input v-model.number="s.price_min" type="number" min="0" placeholder="Min" class="rounded-md border-slate-300 text-sm" @change="submit" />
                            <input v-model.number="s.price_max" type="number" min="0" :placeholder="facets.price_max ? `Max ${money(facets.price_max)}` : 'Max'" class="rounded-md border-slate-300 text-sm" @change="submit" />
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button v-for="b in [25000, 50000, 100000]" :key="b" type="button" class="rounded-full border px-2.5 py-1 text-xs" :class="Number(s.price_max) === b ? 'border-[#006ce4] bg-blue-50 text-[#006ce4]' : 'border-slate-300 text-slate-600'" @click="s.price_min = ''; s.price_max = b; submit()">
                                ≤ {{ money(b) }}
                            </button>
                        </div>
                    </div>
                    <div class="border-b px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Type d'hebergement</p>
                        <label v-for="t in facets.types" :key="t.key" class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="rounded border-slate-400 text-[#006ce4]" :checked="s.type.includes(t.key)" @change="toggle('type', t.key)" />
                            <span class="flex-1">{{ t.label }}</span><span class="text-xs text-slate-500">{{ t.count }}</span>
                        </label>
                    </div>
                    <div v-if="facets.amenities.length" class="border-b px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Equipements</p>
                        <label v-for="a in facets.amenities" :key="a.key" class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="rounded border-slate-400 text-[#006ce4]" :checked="s.amenities.includes(a.key)" @change="toggle('amenities', a.key)" />
                            <span class="flex-1">{{ a.label }}</span><span class="text-xs text-slate-500">{{ a.count }}</span>
                        </label>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-sm font-bold text-slate-900">Chambres</p>
                        <div class="mt-2 flex gap-1.5">
                            <button v-for="n in ['', 1, 2, 3, 4]" :key="n" type="button" class="rounded-md border px-3 py-1 text-sm" :class="String(s.bedrooms) === String(n) ? 'border-[#006ce4] bg-blue-50 text-[#006ce4]' : 'border-slate-300 text-slate-600'" @click="s.bedrooms = n; submit()">
                                {{ n === '' ? 'Toutes' : n + '+' }}
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Resultats -->
            <div class="min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-extrabold text-slate-900">
                        {{ s.city ? `${s.city} : ` : '' }}{{ listings.total ?? listings.data.length }} etablissement(s) trouve(s)
                    </h2>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold lg:hidden" @click="filtersOpen = !filtersOpen">
                            Filtres<span v-if="activeFilters"> ({{ activeFilters }})</span>
                        </button>
                        <select v-model="s.sort" class="rounded-full border-slate-300 py-1.5 pl-3 pr-8 text-sm font-semibold" @change="submit">
                            <option v-for="(label, key) in sorts" :key="key" :value="key">Trier : {{ label }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    <article v-for="l in listings.data" :key="l.id" class="flex flex-col overflow-hidden rounded-xl border bg-white transition hover:shadow-md sm:flex-row">
                        <Link :href="href(l)" class="relative shrink-0 sm:w-60">
                            <ListingImage :src="l.image_url" fallback-class="bg-[#003b95]" class="h-48 sm:h-full">{{ l.city }}</ListingImage>
                            <PromoBadge :promo="l.promo" class="absolute left-2 top-2" />
                        </Link>
                        <div class="flex min-w-0 flex-1 flex-col gap-3 p-4 md:flex-row">
                            <div class="min-w-0 flex-1">
                                <Link :href="href(l)" class="text-lg font-bold leading-tight text-[#006ce4] hover:underline">{{ l.title }}</Link>
                                <p class="mt-1 text-sm">
                                    <button type="button" class="font-semibold text-[#006ce4] underline" @click="goCity(l.city)">{{ l.city }}</button>
                                    <span class="text-slate-500"> · {{ l.address || l.country }}</span>
                                </p>
                                <p v-if="l.property_type_label" class="mt-2 inline-block rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ l.property_type_label }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ l.bedrooms }} chambre(s) · {{ l.bathrooms }} salle(s) de bain · jusqu'a {{ l.max_guests }} voyageurs
                                </p>
                                <div v-if="l.amenities?.length" class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
                                    <span v-for="a in l.amenities.slice(0, 5)" :key="a">{{ AMENITY_ICONS[a] ?? '•' }} {{ amenityLabel(a) }}</span>
                                </div>
                                <p class="mt-2 text-sm font-semibold text-green-700">✓ Annulation gratuite jusqu'a 48 h avant</p>
                                <p class="text-sm text-green-700">✓ Paiement par Mobile Money</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end justify-between gap-3 text-right md:w-48">
                                <span v-if="l.owner_id" class="rounded bg-green-50 px-2 py-1 text-[11px] font-semibold text-green-700">Partenaire verifie</span>
                                <span v-else class="rounded bg-blue-50 px-2 py-1 text-[11px] font-semibold text-[#003b95]">Selection MOKILI TOUR</span>
                                <div>
                                    <p v-if="l.booked_on_dates" class="mb-1 text-xs font-semibold text-red-600">Deja reserve sur vos dates</p>
                                    <p class="text-xs text-slate-500">{{ nights ? `${nights} nuit(s), ${s.adults} adulte(s)` : '1 nuit' }}</p>
                                    <p v-if="l.promo" class="text-sm text-red-600 line-through">{{ money(Number(l.price_per_night) * Math.max(1, nights)) }} {{ l.currency }}</p>
                                    <p class="text-2xl font-bold text-slate-900">{{ money(priceFor(l)) }} {{ l.currency }}</p>
                                    <p class="text-[11px] text-slate-500">Taxes et frais compris</p>
                                    <Link :href="href(l)" class="mt-2 inline-flex items-center gap-1 rounded-md bg-[#006ce4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0057b8]">
                                        Voir les disponibilites ›
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="!listings.data.length" class="mt-6 rounded-xl border bg-white p-10 text-center">
                    <p class="text-lg font-semibold text-slate-900">Aucun hebergement ne correspond a votre recherche.</p>
                    <p class="mt-1 text-sm text-slate-500">Essayez une autre ville ou retirez des filtres.</p>
                    <button v-if="activeFilters" type="button" class="mt-4 font-semibold text-[#006ce4]" @click="resetFilters">Retirer les filtres</button>
                </div>

                <nav v-if="listings.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-1">
                    <template v-for="link in listings.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-scroll class="rounded-md border px-3 py-1.5 text-sm" :class="link.active ? 'border-[#006ce4] bg-[#006ce4] text-white' : 'bg-white text-slate-700'"><span v-html="link.label" /></Link>
                        <span v-else class="rounded-md px-3 py-1.5 text-sm text-slate-400" v-html="link.label" />
                    </template>
                </nav>
            </div>
        </div>

        <PartnersSection module="logement" title="Hotels a Brazzaville" subtitle="Les grands hotels de la capitale, en complement de nos logements." />
    </div>
</template>
