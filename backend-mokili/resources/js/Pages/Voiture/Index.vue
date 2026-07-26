<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ vehicles: { type: Object, required: true } });
</script>
<template>
    <Head title="Voiture" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Voiture</h1>
        <p class="mt-1 text-navy-700">Location de vehicules, toutes categories.</p>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="v in vehicles.data" :key="v.id" :href="`/voiture/${v.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <ListingImage :src="v.image_url" fallback-class="bg-orange-600">{{ v.brand }} {{ v.model }}</ListingImage>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ v.title }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(v.price_per_day).toLocaleString('fr-FR') }} {{ v.currency }} / jour</p>
                </div>
            </Link>
        </div>
        <p v-if="!vehicles.data.length" class="mt-10 text-center text-navy-500">Aucun vehicule pour le moment.</p>
    </div>
</template>
