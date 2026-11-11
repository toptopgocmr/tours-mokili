<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { usePreviewPhotos } from '@/composables/usePreviewPhotos';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

// TOURISME facon Boas "Sites touristiques" : un en-tete, un panneau de
// filtres (ville, recherche), des onglets par type de sortie et une seule
// grille de cartes. "Tout" affiche TOUTES les sorties de tous les onglets.
const props = defineProps({
    tabs: { type: Object, default: () => ({}) },
    tab: { type: String, default: null },
    filters: { type: Object, default: () => ({}) },
    parkCategories: { type: Object, default: () => ({}) },
    cities: { type: Array, default: () => [] },
    counts: { type: Object, default: null },
    cards: { type: Object, default: () => ({ data: [], total: 0, page: 1, last_page: 1 }) },
});

const { photoFor, isPreview, isIllustration } = usePreviewPhotos();
const s = reactive({ city: props.filters.city ?? '', q: props.filters.q ?? '', type: props.filters.type ?? '' });

const TITLES = {
    null: ['Decouvrez les plus beaux tresors du Congo', 'Sites touristiques, parcs, plages, concerts, restaurants et sorties. Vivez des moments inoubliables avec nos partenaires locaux.'],
    concerts: ['Concerts & spectacles', 'Les prochains concerts, spectacles et soirees, billets en ligne.'],
    cinema: ['Cinema', 'Les seances a l\'affiche pres de chez vous.'],
    parcs: ["Parcs d'attractions & loisirs", 'Parcs aquatiques, zoos, iles de loisirs, aires de jeux et parcs naturels.'],
    night_club: ['Night clubs', 'Les meilleures boites de nuit pour faire la fete.'],
    restaurant: ['Restaurants', 'Cuisine congolaise, africaine et internationale.'],
    bar_lounge: ['Bars lounge', 'Terrasses, cocktails et ambiance lounge.'],
    visite: ['Sites touristiques', 'Monuments, musees, plages, cascades et patrimoine culturel.'],
};
const title = computed(() => TITLES[props.tab] ?? TITLES.null);

const go = (extra = {}) => {
    const query = Object.fromEntries(Object.entries({ tab: props.tab, city: s.city, q: s.q, type: props.tab === 'parcs' ? s.type : '', ...extra }).filter(([, v]) => v));
    router.get('/divertissement', query, { preserveState: true, preserveScroll: true, replace: true });
};
const setTab = (tab) => {
    s.type = '';
    go({ tab: tab || undefined, type: undefined, page: undefined });
};
const setPage = (page) => go({ page: page > 1 ? page : undefined });
const reset = () => {
    s.city = '';
    s.q = '';
    s.type = '';
    go({ city: undefined, q: undefined, type: undefined, page: undefined });
};

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : null);
const asBiz = (c) => ({ id: c.business_id, image_url: c.image, image_is_illustration: c.image_is_illustration });
const imageOf = (c) => (c.business_id ? photoFor(asBiz(c)) : c.image);
const broken = ref([]);
const count = (key) => (props.counts ? props.counts[key] ?? 0 : null);
const total = computed(() => (props.counts ? Object.values(props.counts).reduce((a, b) => a + b, 0) : props.cards.total));
const excursion = 'https://wa.me/?text=' + encodeURIComponent("Bonjour MOKILI TOUR, je souhaite organiser une visite sur mesure (excursion, circuit, groupe). Pouvez-vous m'aider ?");
</script>

<template>
    <Head :title="title[0]" />

    <!-- En-tete facon Boas -->
    <section class="bg-gradient-to-b from-emerald-50 via-white to-white px-4 pb-8 pt-8">
        <div class="mx-auto max-w-6xl">
            <nav class="text-xs text-slate-500">
                <Link href="/" class="hover:text-navy-900">Accueil</Link> ›
                <Link href="/divertissement" class="hover:text-navy-900">Tourisme</Link>
                <template v-if="tab"> › <span class="text-slate-700">{{ tabs[tab] }}</span></template>
            </nav>
            <span class="mt-4 inline-block rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Tourisme · {{ tab ? tabs[tab] : 'Toutes les sorties' }}</span>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight text-navy-900 sm:text-5xl">
                <template v-if="!tab">Decouvrez les plus beaux <span class="text-emerald-600">tresors</span> du Congo</template>
                <template v-else>{{ title[0] }}</template>
            </h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ title[1] }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 pb-12">
        <!-- Onglets -->
        <div class="flex flex-wrap gap-2">
            <button type="button" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition" :class="!tab ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'" @click="setTab(null)">
                Tout<span v-if="!tab" class="ml-1 opacity-70">({{ total }})</span>
            </button>
            <button
                v-for="(label, key) in tabs"
                :key="key"
                type="button"
                class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition"
                :class="tab === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'"
                @click="setTab(key)"
            >
                {{ label }}<span v-if="counts" class="ml-1 opacity-60">({{ count(key) }})</span>
            </button>
            <Link href="/tourisme/monde" class="shrink-0 rounded-full bg-gold-500 px-4 py-2 text-sm font-semibold text-navy-900 hover:bg-gold-400">Decouvrir le monde</Link>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <!-- Filtres -->
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24">
                <p class="flex items-center gap-2 font-bold text-slate-900">
                    <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4" /></svg>
                    Filtres
                </p>
                <form class="mt-4 space-y-4" @submit.prevent="go({ page: undefined })">
                    <label class="block">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Ville</span>
                        <select v-model="s.city" class="mt-1 w-full rounded-xl border-slate-300 text-sm" @change="go({ page: undefined })">
                            <option value="">Toutes les villes</option>
                            <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Rechercher</span>
                        <input v-model="s.q" type="search" placeholder="Nom du site, lieu..." class="mt-1 w-full rounded-xl border-slate-300 text-sm" />
                    </label>
                    <label v-if="tab === 'parcs'" class="block">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Type de parc</span>
                        <select v-model="s.type" class="mt-1 w-full rounded-xl border-slate-300 text-sm" @change="go({ page: undefined })">
                            <option value="">Tous les parcs</option>
                            <option v-for="(label, key) in parkCategories" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Rechercher</button>
                    <button v-if="filters.city || filters.q || filters.type" type="button" class="w-full text-xs font-semibold text-slate-500 underline" @click="reset">Effacer les filtres</button>
                </form>
            </aside>

            <!-- Resultats -->
            <div class="min-w-0">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-lg font-extrabold text-navy-900">{{ cards.total }} {{ tab ? tabs[tab].toLowerCase() : 'sorties et sites' }}</p>
                    <p v-if="cards.last_page > 1" class="text-sm text-slate-500">Page {{ cards.page }} / {{ cards.last_page }}</p>
                </div>

                <div class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <article v-for="c in cards.data" :key="c.key" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-xl">
                        <Link :href="c.href" class="relative block h-48 overflow-hidden bg-slate-100">
                            <img v-if="imageOf(c) && !broken.includes(c.key)" :src="imageOf(c)" :alt="c.title" referrerpolicy="no-referrer" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" @error="broken.push(c.key)" />
                            <GuideCover v-else :src="null" :category="c.category || 'site_touristique'" :alt="c.title" icon-class="h-12 w-12" />
                            <span v-if="c.price" class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold text-emerald-700 shadow">{{ c.price }}</span>
                            <span v-if="c.city" class="absolute right-3 top-3 rounded-full bg-navy-900/80 px-2.5 py-1 text-xs font-semibold text-white">{{ c.city }}</span>
                            <span v-if="c.business_id && isIllustration(asBiz(c))" class="absolute bottom-3 right-3 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold text-white">Photo d'illustration</span>
                            <span v-if="c.business_id && isPreview(asBiz(c))" class="absolute bottom-3 left-3 rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-950">Apercu admin</span>
                        </Link>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600">{{ c.category && c.tab === 'parcs' ? c.category : c.category_label && ['cinema', 'salle_spectacle'].includes(c.category) ? c.category_label : c.tab_label }}</p>
                            <Link :href="c.href" class="mt-0.5 text-lg font-bold leading-tight text-navy-900 hover:text-emerald-700">{{ c.title }}</Link>
                            <p v-if="date(c.date)" class="mt-1 text-sm font-semibold text-purple-700">{{ date(c.date) }}</p>
                            <p v-if="c.address" class="mt-1 line-clamp-1 text-sm text-slate-500">{{ c.address }}</p>
                            <p v-if="c.description" class="mt-2 line-clamp-3 text-sm text-slate-600">{{ c.description }}</p>
                            <Link :href="c.href" class="mt-auto pt-4 text-sm font-bold text-emerald-700 group-hover:underline">Decouvrir →</Link>
                        </div>
                    </article>
                </div>

                <div v-if="!cards.data.length" class="mt-6 rounded-2xl border bg-white p-10 text-center">
                    <p class="text-lg font-semibold text-slate-900">Rien ne correspond a votre recherche.</p>
                    <button type="button" class="mt-3 font-semibold text-emerald-700" @click="reset">Effacer les filtres</button>
                </div>

                <!-- Pagination -->
                <nav v-if="cards.last_page > 1" class="mt-8 flex flex-wrap items-center justify-center gap-1">
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-40" :disabled="cards.page <= 1" @click="setPage(cards.page - 1)">‹ Precedent</button>
                    <button v-for="n in cards.last_page" :key="n" type="button" class="h-9 w-9 rounded-lg text-sm font-semibold" :class="n === cards.page ? 'bg-navy-900 text-white' : 'border bg-white text-slate-700'" @click="setPage(n)">{{ n }}</button>
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-40" :disabled="cards.page >= cards.last_page" @click="setPage(cards.page + 1)">Suivant ›</button>
                </nav>
            </div>
        </div>

        <!-- Visite sur mesure -->
        <section class="mt-14 grid gap-6 overflow-hidden rounded-3xl bg-navy-900 p-8 text-white md:grid-cols-[1.4fr_1fr] md:p-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-gold-400">Visite sur mesure</p>
                <h2 class="mt-2 text-3xl font-extrabold">Organisez l'aventure de vos reves</h2>
                <p class="mt-2 text-white/80">Excursion privee, circuit thematique, groupe scolaire ou seminaire d'entreprise : nous concevons votre itineraire et nous occupons de toute la logistique (transport, hebergement, guides).</p>
                <a :href="excursion" target="_blank" rel="noopener" class="mt-5 inline-block rounded-full bg-gold-500 px-6 py-3 font-bold text-navy-900 hover:bg-gold-400">Concevoir mon excursion</a>
            </div>
            <div class="grid grid-cols-2 gap-3 self-center">
                <div v-for="[k, v] in [['Guides', 'Pro'], ['Groupes', '2-50+'], ['Villes', 'Congo & monde'], ['Support', '24/7']]" :key="k" class="rounded-2xl bg-white/10 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-white/60">{{ k }}</p>
                    <p class="mt-1 text-xl font-extrabold text-gold-400">{{ v }}</p>
                </div>
            </div>
        </section>
    </div>
</template>
