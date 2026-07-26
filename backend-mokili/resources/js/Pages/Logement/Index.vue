<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ listings: { type: Object, required: true } });
</script>
<template>
    <Head title="Logement" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Logement</h1>
        <p class="mt-1 text-navy-700">Appartements, maisons et residences a louer.</p>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="l in listings.data" :key="l.id" :href="`/logement/${l.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <ListingImage :src="l.image_url" fallback-class="bg-green-700">{{ l.city }}</ListingImage>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ l.title }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(l.price_per_night).toLocaleString('fr-FR') }} {{ l.currency }} / nuit</p>
                </div>
            </Link>
        </div>
        <p v-if="!listings.data.length" class="mt-10 text-center text-navy-500">Aucun logement pour le moment.</p>
    </div>
</template>
