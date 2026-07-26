<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ offers: { type: Object, required: true } });

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };
</script>
<template>
    <Head title="Fret" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-navy-900">Fret</h1>
                <p class="mt-1 text-navy-700">Services de transport et d'expedition, tous modes.</p>
            </div>
            <Link href="/fret/mes-envois" class="text-sm font-semibold text-gold-600 hover:underline">Suivre une expedition &rarr;</Link>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="o in offers.data" :key="o.id" :href="`/fret/${o.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} &rarr; {{ o.destination_city }}</ListingImage>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ o.title }}</p>
                    <p class="mt-1 text-xs uppercase text-navy-500">{{ modeLabel[o.mode] }} - {{ o.origin_city }} &rarr; {{ o.destination_city }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(o.price_per_kg).toLocaleString('fr-FR') }} {{ o.currency }} / kg</p>
                </div>
            </Link>
        </div>
        <p v-if="!offers.data.length" class="mt-10 text-center text-navy-500">Aucune offre de fret pour le moment.</p>
    </div>
</template>
