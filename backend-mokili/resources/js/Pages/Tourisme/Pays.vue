<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

// TOURISME : premiere page de la rubrique. On choisit un pays, puis on
// voit ses sorties (Congo : catalogue MOKILI TOUR) ou ses villes et sites.
const props = defineProps({
    countries: { type: Array, default: () => [] },
    continents: { type: Array, default: () => [] },
    localCount: { type: Number, default: 0 },
});

const q = ref('');
const continent = ref('');
const norm = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const local = computed(() => props.countries.find((c) => c.local));
const list = computed(() => {
    const n = norm(q.value.trim());
    return props.countries.filter(
        (c) =>
            !c.local &&
            (!continent.value || c.continent === continent.value) &&
            (!n || norm(c.name).includes(n) || c.cities.some((v) => norm(v).includes(n))),
    );
});
const countOf = (cont) => props.countries.filter((c) => !c.local && c.continent === cont).length;
const localMatches = computed(() => {
    const n = norm(q.value.trim());
    return local.value && (!continent.value || continent.value === local.value.continent) && (!n || norm(local.value.name).includes(n) || local.value.cities.some((v) => norm(v).includes(n)));
});
const broken = ref([]);
</script>

<template>
    <Head title="Tourisme - Choisissez un pays" />

    <PhotoHero class="px-4 pb-24 pt-10 text-white sm:pt-12">
        <div class="mx-auto max-w-6xl">
            <nav class="text-xs text-white/80">
                <Link href="/" class="hover:text-white">Accueil</Link> › <span class="text-white">Tourisme</span>
            </nav>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight drop-shadow sm:text-5xl">
                Ou voulez-vous <span class="text-gold-400">sortir</span> ?
            </h1>
            <p class="mt-2 max-w-2xl text-white/90">Choisissez un pays pour decouvrir ses sites touristiques, parcs, concerts, restaurants et sorties.</p>
        </div>
    </PhotoHero>

    <div class="relative z-10 -mt-16 px-4">
        <div class="mx-auto max-w-6xl rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgba(0,0,0,0.18)]">
            <input
                v-model="q"
                type="search"
                placeholder="Rechercher un pays ou une ville : Cameroun, Paris, Dubai..."
                class="h-12 w-full rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500"
            />
            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                    :class="!continent ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-400'"
                    @click="continent = ''"
                >
                    Tous les pays <span class="opacity-70">({{ countries.length }})</span>
                </button>
                <button
                    v-for="c in continents"
                    :key="c"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                    :class="continent === c ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-400'"
                    @click="continent = c"
                >
                    {{ c }} <span class="opacity-60">({{ countOf(c) + (local && local.continent === c ? 1 : 0) }})</span>
                </button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <!-- Le Congo : catalogue MOKILI TOUR -->
        <Link
            v-if="localMatches"
            :href="local.href"
            class="group grid overflow-hidden rounded-2xl border-2 border-gold-400 bg-white shadow-sm transition hover:shadow-lg md:grid-cols-[2fr_3fr]"
        >
            <div class="relative h-48 bg-gradient-to-br from-emerald-600 via-gold-500 to-red-600 md:h-full">
                <img v-if="!broken.includes(local.slug)" :src="local.flag" :alt="local.name" class="absolute inset-0 h-full w-full object-cover opacity-90 transition group-hover:scale-105" @error="broken.push(local.slug)" />
                <span class="absolute left-3 top-3 rounded-full bg-navy-900 px-3 py-1 text-xs font-bold text-white">Partenaires MOKILI TOUR</span>
            </div>
            <div class="p-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Notre pays</p>
                <h2 class="mt-1 text-2xl font-extrabold text-navy-900 sm:text-3xl">{{ local.name }}</h2>
                <p class="mt-2 text-slate-600">Concerts & spectacles, cinema, parcs, night clubs, restaurants, bars lounge et visites, avec reservation et paiement en ligne.</p>
                <p class="mt-3 text-sm text-slate-500">{{ local.cities.join(' · ') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span v-if="localCount" class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ localCount }} sorties et sites</span>
                    <span class="rounded-full bg-gold-500 px-5 py-2 text-sm font-bold text-navy-900 group-hover:bg-gold-400">Voir les sorties →</span>
                </div>
            </div>
        </Link>

        <!-- Les autres pays -->
        <div class="mt-8 flex items-end justify-between">
            <h2 class="text-xl font-extrabold text-navy-900">{{ continent || 'Le monde' }}</h2>
            <p class="text-sm text-slate-500">{{ list.length }} pays</p>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="c in list"
                :key="c.slug"
                :href="c.href"
                class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg"
            >
                <div class="relative h-32 bg-gradient-to-br from-navy-900 to-gold-600">
                    <img v-if="!broken.includes(c.slug)" :src="c.flag" :alt="`Drapeau ${c.name}`" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition group-hover:scale-105" @error="broken.push(c.slug)" />
                    <span class="absolute right-2 top-2 rounded-full bg-navy-900/80 px-2 py-0.5 text-[11px] font-semibold text-white">{{ c.continent }}</span>
                </div>
                <div class="p-4">
                    <p class="font-bold text-navy-900">{{ c.name }}</p>
                    <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ c.cities.join(' · ') }}</p>
                    <p class="mt-2 text-sm font-semibold text-gold-600 group-hover:underline">{{ c.cities.length }} villes a decouvrir →</p>
                </div>
            </Link>
        </div>
        <p v-if="!list.length && !localMatches" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
            Aucun pays ne correspond. <Link :href="`/tourisme/monde?ville=${encodeURIComponent(q)}`" class="font-semibold text-navy-900 underline">Chercher « {{ q }} » dans le monde</Link>
        </p>
        <div class="mt-8 text-center">
            <Link href="/tourisme/monde" class="inline-block rounded-full border border-navy-200 px-5 py-2 text-sm font-semibold text-navy-900 hover:bg-navy-50">Une autre ville ? Rechercher partout dans le monde</Link>
        </div>
    </div>
</template>
