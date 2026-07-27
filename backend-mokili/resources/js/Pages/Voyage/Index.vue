<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const destination = ref(props.filters.destination ?? '');
const type = ref(props.filters.type ?? '');

const types = [
    { value: '', label: 'Tous' },
    { value: 'vol', label: 'Vols' },
    { value: 'sejour', label: 'Sejours' },
    { value: 'circuit', label: 'Circuits' },
];

const search = () => {
    const query = Object.fromEntries(
        Object.entries({ destination: destination.value, type: type.value }).filter(([, v]) => v)
    );
    router.get('/voyage', query, { preserveState: true, preserveScroll: true, replace: true });
};

const setType = (value) => {
    type.value = value;
    search();
};
</script>

<template>
    <Head title="Voyage" />

    <!-- Hero + search: style Booking/Kayak (recherche flottante par
         dessus la photo, onglets de type comme leurs onglets Vols/
         Hebergements/Voitures). -->
    <section class="relative h-[260px] overflow-hidden sm:h-[300px]">
        <img src="/images/hero.jpg" alt="Voyage" class="h-full w-full object-cover object-[center_20%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative flex h-full max-w-3xl flex-col justify-start px-6 pt-14 text-left sm:px-10 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Voyage</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">Ou voulez-vous partir ?</h1>
            <p class="mt-1 max-w-lg text-sm text-white/90 drop-shadow">
                Vols, sejours et circuits vers votre prochaine destination.
            </p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-3">
                <button
                    v-for="t in types"
                    :key="t.value"
                    type="button"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="type === t.value ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                    @click="setType(t.value)"
                >
                    {{ t.label }}
                </button>
            </div>
            <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="search">
                <input
                    v-model="destination"
                    type="text"
                    placeholder="Destination (ex: Paris, Dubai...)"
                    class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600"
                />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="offer in offers.data"
                :key="offer.id"
                :href="`/voyage/${offer.slug}`"
                class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg"
            >
                <div class="relative h-36 overflow-hidden">
                    <DestinationImage :city="offer.destination_city" :image="offer.image_url" class="h-full w-full" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent" />
                    <span class="absolute bottom-2 left-3 font-semibold text-white drop-shadow">{{ offer.destination_city }}</span>
                </div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ offer.title }}</p>
                    <p class="text-xs uppercase text-navy-500">{{ offer.type }} - {{ offer.airline }}</p>
                    <p class="mt-2 text-sm font-semibold text-gold-600">
                        {{ Number(offer.price).toLocaleString('fr-FR') }} {{ offer.currency }}
                    </p>
                </div>
            </Link>
        </div>

        <p v-if="!offers.data.length" class="mt-10 text-center text-navy-500">Aucune offre pour le moment.</p>
    </div>
</template>
