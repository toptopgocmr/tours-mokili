<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = reactive({
    city: props.filters.city ?? '',
    category: props.filters.category ?? '',
});

const categories = [
    { value: '', label: 'Tout' },
    { value: 'concert', label: 'Concerts' },
    { value: 'sport', label: 'Sport' },
    { value: 'spectacle', label: 'Spectacles' },
    { value: 'cinema', label: 'Cinema' },
];

const submitSearch = () => {
    const query = Object.fromEntries(Object.entries(search).filter(([, v]) => v));
    router.get('/divertissement', query, { preserveState: true, preserveScroll: true, replace: true });
};

const setCategory = (value) => {
    search.category = value;
    submitSearch();
};
</script>

<template>
    <Head title="Divertissement" />

    <!-- Hero + search: style Booking.com "Attractions, activites et
         experiences" (barre de recherche flottante par-dessus la
         photo, filtres par categorie en dessous). -->
    <section class="relative h-[260px] overflow-hidden sm:h-[300px]">
        <img src="/images/hero.jpg" alt="Divertissement" class="h-full w-full object-cover object-[center_40%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-14 text-left sm:px-10 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Divertissement</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">Concerts, spectacles et sorties</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">
                Concerts, spectacles, sport et billetterie pres de chez vous.
            </p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-3">
                <button
                    v-for="c in categories"
                    :key="c.value"
                    type="button"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="search.category === c.value ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                    @click="setCategory(c.value)"
                >
                    {{ c.label }}
                </button>
            </div>
            <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="submitSearch">
                <input
                    v-model="search.city"
                    type="text"
                    placeholder="Ville ou salle (ex: Douala, Kinshasa...)"
                    class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600"
                />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="e in events.data" :key="e.id" :href="`/divertissement/${e.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <ListingImage :src="e.image_url" fallback-class="bg-purple-700">{{ e.venue ?? e.city }}</ListingImage>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ e.title }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(e.price).toLocaleString('fr-FR') }} {{ e.currency }}</p>
                </div>
            </Link>
        </div>
        <p v-if="!events.data.length" class="mt-10 text-center text-navy-500">Aucun evenement pour le moment.</p>
    </div>
</template>
