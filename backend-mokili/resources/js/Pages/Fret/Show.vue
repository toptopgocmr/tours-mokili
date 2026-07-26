<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ offer: { type: Object, required: true } });

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };
</script>
<template>
    <Head :title="offer.title" />
    <div class="mx-auto max-w-3xl px-6 py-12">
        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <ListingImage :src="offer.image_url" fallback-class="bg-teal-700 h-56" class="h-56">{{ offer.origin_city }} &rarr; {{ offer.destination_city }}</ListingImage>
            <div class="p-6">
                <h1 class="text-2xl font-bold text-navy-900">{{ offer.title }}</h1>
                <p class="mt-1 text-sm uppercase text-navy-500">{{ modeLabel[offer.mode] }}</p>
                <p v-if="offer.description" class="mt-4 text-navy-700">{{ offer.description }}</p>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-navy-500">Origine</dt><dd class="font-medium">{{ offer.origin_city }}</dd></div>
                    <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ offer.destination_city }}</dd></div>
                    <div><dt class="text-navy-500">Prix</dt><dd class="font-semibold text-gold-600">{{ Number(offer.price_per_kg).toLocaleString('fr-FR') }} {{ offer.currency }} / kg</dd></div>
                    <div v-if="offer.capacity_kg"><dt class="text-navy-500">Capacite</dt><dd class="font-medium">{{ offer.capacity_kg }} kg</dd></div>
                </dl>
            </div>
        </div>
    </div>
</template>
