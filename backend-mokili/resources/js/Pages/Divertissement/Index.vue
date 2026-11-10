<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ParkCard from '@/Components/ParkCard.vue';
import BusinessCard from '@/Components/BusinessCard.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: MainLayout });

// TOURISME (ex-Divertissement) : un onglet par type de sortie.
const props = defineProps({
    tabs: { type: Object, default: () => ({}) },
    tab: { type: String, default: null },
    source: { type: String, default: null },
    items: { type: Object, default: null },
    preview: { type: Object, default: null },
    parkCategories: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = reactive({ city: props.filters.city ?? '', type: props.filters.type ?? '' });
const ICONS = { concerts: '🎤', cinema: '🎬', parcs: '🎡', night_club: '🪩', restaurant: '🍽️', bar_lounge: '🍸', visite: '📸' };
const HERO = {
    concerts: 'Concerts & spectacles',
    cinema: 'Cinema',
    parcs: "Parcs d'attractions, zoos & loisirs",
    night_club: 'Night clubs',
    restaurant: 'Restaurants',
    bar_lounge: 'Bars lounge',
    visite: 'Visites & sites touristiques',
};

const go = (tab, extra = {}) => {
    const query = Object.fromEntries(Object.entries({ tab, city: search.city, ...extra }).filter(([, v]) => v));
    router.get('/divertissement', query, { preserveState: true, preserveScroll: true, replace: true });
};
const setTab = (tab) => {
    search.type = '';
    go(tab);
};
const submit = () => go(props.tab, { type: props.tab === 'parcs' ? search.type : '' });
const setParkType = (t) => {
    search.type = search.type === t ? '' : t;
    go('parcs', { type: search.type });
};

const list = computed(() => props.items?.data ?? []);
const emptyText = computed(() => ({
    events: 'Aucun evenement a venir pour le moment.',
    parks: 'Aucun parc ne correspond a votre recherche.',
    businesses: 'Aucune adresse pour le moment dans cette rubrique.',
}[props.source] ?? ''));
const eventDate = (e) => new Date(e.starts_at).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
</script>

<template>
    <Head :title="tab ? HERO[tab] : 'Tourisme'" />

    <section class="relative h-[260px] overflow-hidden sm:h-[300px]">
        <img src="/images/hero.jpg" alt="Tourisme" class="h-full w-full object-cover object-[center_40%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-14 text-left sm:px-10 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Tourisme</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">{{ tab ? HERO[tab] : 'Sortir, decouvrir, visiter' }}</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">
                Concerts, spectacles, cinema, parcs, night clubs, restaurants, bars lounge et visites au Congo.
            </p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-5xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-3">
                <button type="button" class="rounded-full px-4 py-1.5 text-sm font-semibold transition" :class="!tab ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'" @click="setTab(null)">Tout</button>
                <button
                    v-for="(label, key) in tabs"
                    :key="key"
                    type="button"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="tab === key ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                    @click="setTab(key)"
                >
                    <span class="mr-1">{{ ICONS[key] }}</span>{{ label }}
                </button>
            </div>
            <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="submit">
                <input v-model="search.city" type="text" placeholder="Ville (ex: Brazzaville, Pointe-Noire...)" class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600" />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <!-- Un onglet -->
        <template v-if="tab">
            <div v-if="tab === 'parcs'" class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="(label, key) in parkCategories"
                    :key="key"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                    :class="search.type === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700 hover:border-navy-400'"
                    @click="setParkType(key)"
                >
                    {{ label }}
                </button>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <template v-if="source === 'events'">
                    <Link v-for="e in list" :key="e.id" :href="`/divertissement/${e.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                        <ListingImage :src="e.image_url" fallback-class="bg-purple-700">{{ e.venue ?? e.city }}</ListingImage>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase text-purple-700">{{ eventDate(e) }} · {{ e.city }}</p>
                            <p class="font-semibold text-navy-900">{{ e.title }}</p>
                            <p class="mt-1 text-sm font-semibold text-gold-600"><span v-if="e.promo" class="mr-1 text-xs font-normal text-navy-400 line-through">{{ Number(e.price).toLocaleString('fr-FR') }}</span>{{ Number(e.final_price ?? e.price).toLocaleString('fr-FR') }} {{ e.currency }} <PromoBadge :promo="e.promo" /></p>
                        </div>
                    </Link>
                </template>
                <template v-else-if="source === 'parks'">
                    <ParkCard v-for="p in list" :key="p.id" :park="p" />
                </template>
                <template v-else>
                    <BusinessCard v-for="b in list" :key="b.id" :business="b" />
                </template>
            </div>
            <p v-if="!list.length" class="mt-10 text-center text-navy-500">{{ emptyText }}</p>
            <Paginator v-if="items" :page="items" />
        </template>

        <!-- Tout : apercu de chaque rubrique -->
        <template v-else-if="preview">
            <section v-if="preview.events.length">
                <div class="flex items-end justify-between">
                    <h2 class="text-2xl font-bold text-navy-900">🎤 A l'affiche</h2>
                    <button type="button" class="text-sm font-semibold text-gold-600 hover:underline" @click="setTab('concerts')">Voir tout →</button>
                </div>
                <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="e in preview.events" :key="e.id" :href="`/divertissement/${e.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                        <ListingImage :src="e.image_url" fallback-class="bg-purple-700">{{ e.venue ?? e.city }}</ListingImage>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase text-purple-700">{{ eventDate(e) }} · {{ e.city }}</p>
                            <p class="font-semibold text-navy-900">{{ e.title }}</p>
                            <p class="mt-1 text-sm font-semibold text-gold-600"><span v-if="e.promo" class="mr-1 text-xs font-normal text-navy-400 line-through">{{ Number(e.price).toLocaleString('fr-FR') }}</span>{{ Number(e.final_price ?? e.price).toLocaleString('fr-FR') }} {{ e.currency }} <PromoBadge :promo="e.promo" /></p>
                        </div>
                    </Link>
                </div>
            </section>

            <section v-if="preview.parks.length" class="mt-14">
                <div class="flex items-end justify-between">
                    <h2 class="text-2xl font-bold text-navy-900">🎡 Parcs d'attractions & loisirs</h2>
                    <button type="button" class="text-sm font-semibold text-gold-600 hover:underline" @click="setTab('parcs')">Voir tous les parcs →</button>
                </div>
                <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ParkCard v-for="p in preview.parks" :key="p.id" :park="p" />
                </div>
            </section>

            <section v-if="preview.places.length" class="mt-14">
                <h2 class="text-2xl font-bold text-navy-900">🍸 Restaurants, bars lounge, night clubs & visites</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <BusinessCard v-for="b in preview.places" :key="b.id" :business="b" />
                </div>
            </section>

            <p v-if="!preview.events.length && !preview.parks.length && !preview.places.length" class="mt-10 text-center text-navy-500">Rien pour le moment dans cette ville.</p>
        </template>
    </div>
</template>
