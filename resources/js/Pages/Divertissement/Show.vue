<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ event: { type: Object, required: true } });
</script>
<template>
    <Head :title="event.title" />
    <div class="mx-auto max-w-3xl px-6 py-12">
        <div class="overflow-hidden rounded-2xl">
            <ListingImage :src="event.image_url" fallback-class="bg-purple-700 text-xl font-semibold" class="h-56">{{ event.venue ?? event.city }}</ListingImage>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ event.title }}</h1>
        <p class="mt-2 text-navy-700">{{ event.description }}</p>
        <p class="mt-4 text-xl font-bold text-gold-600">{{ Number(event.price).toLocaleString('fr-FR') }} {{ event.currency }}</p>
        <p class="mt-6 rounded-lg bg-navy-50 p-4 text-sm text-navy-700">Module en cours de finalisation - la billetterie suivra le meme flux que Voyage (booking + verification Peex).</p>
    </div>
</template>
