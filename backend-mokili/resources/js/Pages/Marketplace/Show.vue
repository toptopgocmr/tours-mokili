<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ServiceIcon from '@/Components/ServiceIcon.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ product: { type: Object, required: true } });
</script>
<template>
    <Head :title="product.title" />
    <div class="mx-auto max-w-3xl px-6 py-12">
        <div class="overflow-hidden rounded-2xl">
            <ListingImage :src="product.image_url" fallback-class="bg-pink-700 text-xl font-semibold" class="h-56">
                <div class="flex flex-col items-center gap-2">
                    <ServiceIcon slug="marketplace" class="h-12 w-12 text-white" />
                    <span>{{ product.category ?? 'Produit' }}</span>
                </div>
            </ListingImage>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ product.title }}</h1>
        <p class="mt-2 text-navy-700">{{ product.description }}</p>
        <p class="mt-4 text-xl font-bold text-gold-600">{{ Number(product.price).toLocaleString('fr-FR') }} {{ product.currency }}</p>
        <p class="mt-6 rounded-lg bg-navy-50 p-4 text-sm text-navy-700">Module en cours de finalisation - la commande suivra le meme flux que Voyage (booking + verification Peex).</p>
    </div>
</template>
