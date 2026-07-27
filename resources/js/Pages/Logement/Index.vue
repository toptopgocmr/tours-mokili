<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    listings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = reactive({
    city: props.filters.city ?? '',
    check_in: props.filters.check_in ?? '',
    check_out: props.filters.check_out ?? '',
    guests: props.filters.guests ?? '',
    price_min: props.filters.price_min ?? '',
    price_max: props.filters.price_max ?? '',
});

const submitSearch = () => {
    const query = Object.fromEntries(Object.entries(search).filter(([, v]) => v !== '' && v !== null));
    router.get('/logement', query, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    search.city = '';
    search.check_in = '';
    search.check_out = '';
    search.guests = '';
    search.price_min = '';
    search.price_max = '';
    router.get('/logement', {}, { preserveState: true, preserveScroll: true, replace: true });
};

const bookingHref = (l) => {
    const params = new URLSearchParams();
    if (search.check_in) params.set('check_in', search.check_in);
    if (search.check_out) params.set('check_out', search.check_out);
    if (search.guests) params.set('guests', search.guests);
    const qs = params.toString();
    return `/logement/${l.slug}` + (qs ? `?${qs}` : '');
};
</script>

<template>
    <Head title="Logement" />
    <div class="bg-navy-900 py-8">
        <div class="mx-auto max-w-7xl px-6">
            <h1 class="text-2xl font-bold text-white">Trouvez votre logement</h1>
            <p class="mt-1 text-sm text-navy-200">Appartements, maisons et residences a louer partout au Congo.</p>

            <form
                class="mt-5 grid gap-3 rounded-2xl bg-white p-4 shadow-lg sm:grid-cols-2 lg:grid-cols-5"
                @submit.prevent="submitSearch"
            >
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold uppercase text-navy-500">Destination</label>
                    <input
                        v-model="search.city"
                        type="text"
                        placeholder="Ville (ex. Brazzaville)"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-navy-500">Arrivee</label>
                    <input v-model="search.check_in" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-navy-500">Depart</label>
                    <input v-model="search.check_out" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-navy-500">Voyageurs</label>
                    <input
                        v-model.number="search.guests"
                        type="number"
                        min="1"
                        placeholder="2"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                    />
                </div>
                <div class="flex items-end lg:col-span-5">
                    <button type="submit" class="btn-gold w-full lg:w-auto lg:px-10">Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="grid gap-8 lg:grid-cols-4">
            <aside class="h-fit rounded-2xl border p-5 shadow-sm lg:col-span-1">
                <p class="font-semibold text-navy-900">Filtrer par prix</p>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-navy-500">Prix min / nuit</label>
                        <input v-model.number="search.price_min" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-navy-500">Prix max / nuit</label>
                        <input v-model.number="search.price_max" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <button type="button" class="btn-console-primary w-full" @click="submitSearch">Appliquer</button>
                    <button type="button" class="w-full text-center text-xs font-medium text-navy-500 underline" @click="resetFilters">
                        Reinitialiser les filtres
                    </button>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <p class="mb-4 text-sm text-navy-500">{{ listings.total ?? listings.data.length }} logement(s) trouve(s)</p>
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <Link v-for="l in listings.data" :key="l.id" :href="bookingHref(l)" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                        <ListingImage :src="l.image_url" fallback-class="bg-green-700">{{ l.city }}</ListingImage>
                        <div class="p-4">
                            <p class="font-semibold text-navy-900">{{ l.title }}</p>
                            <p class="text-xs text-navy-500">{{ l.city }} - jusqu'a {{ l.max_guests }} voyageurs</p>
                            <p class="mt-1 text-sm font-semibold text-gold-600">
                                {{ Number(l.price_per_night).toLocaleString('fr-FR') }} {{ l.currency }} / nuit
                            </p>
                        </div>
                    </Link>
                </div>
                <p v-if="!listings.data.length" class="mt-10 text-center text-navy-500">
                    Aucun logement ne correspond a votre recherche.
                </p>
            </div>
        </div>
    </div>
</template>
