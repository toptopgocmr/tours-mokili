<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ vehicle: { type: Object, required: true } });
</script>
<template>
    <Head :title="vehicle.title" />
    <div class="mx-auto max-w-3xl px-6 py-12">
        <div class="overflow-hidden rounded-2xl">
            <ListingImage :src="vehicle.image_url" fallback-class="bg-orange-600 text-xl font-semibold" class="h-56">{{ vehicle.brand }} {{ vehicle.model }}</ListingImage>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ vehicle.title }}</h1>
        <p class="mt-4 text-xl font-bold text-gold-600">{{ Number(vehicle.price_per_day).toLocaleString('fr-FR') }} {{ vehicle.currency }} / jour</p>
        <p class="mt-6 rounded-lg bg-navy-50 p-4 text-sm text-navy-700">Module en cours de finalisation - la reservation suivra le meme flux que Voyage (booking + verification Peex).</p>
    </div>
</template>
