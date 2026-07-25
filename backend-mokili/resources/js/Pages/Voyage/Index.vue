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

const search = () => router.get('/voyage', { destination: destination.value }, { preserveState: true });
</script>

<template>
    <Head title="Voyage" />

    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Voyage</h1>
        <p class="mt-1 text-navy-700">Vols, sejours et circuits vers votre prochaine destination.</p>

        <form class="mt-6 flex max-w-lg gap-2" @submit.prevent="search">
            <input v-model="destination" type="text" placeholder="Destination (ex: Paris)" class="flex-1 rounded-lg border-gray-300" />
            <button type="submit" class="btn-gold">Rechercher</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="offer in offers.data"
                :key="offer.id"
                :href="`/voyage/${offer.slug}`"
                class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg"
            >
                <div class="relative h-36 overflow-hidden">
                    <DestinationImage :city="offer.destination_city" class="h-full w-full" />
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
