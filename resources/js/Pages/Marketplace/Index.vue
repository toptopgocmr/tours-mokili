<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ products: { type: Object, required: true } });
</script>
<template>
    <Head title="Marketplace" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Marketplace</h1>
        <p class="mt-1 text-navy-700">Achetez et vendez entre particuliers et professionnels.</p>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <Link v-for="p in products.data" :key="p.id" :href="`/marketplace/${p.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <ListingImage :src="p.image_url" fallback-class="bg-pink-700 text-sm" class="h-28">{{ p.category ?? 'Produit' }}</ListingImage>
                <div class="p-3">
                    <p class="text-sm font-semibold text-navy-900">{{ p.title }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(p.price).toLocaleString('fr-FR') }} {{ p.currency }}</p>
                </div>
            </Link>
        </div>
        <p v-if="!products.data.length" class="mt-10 text-center text-navy-500">Aucun produit pour le moment.</p>
    </div>
</template>
