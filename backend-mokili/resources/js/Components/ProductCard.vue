<script setup>
import ListingImage from '@/Components/ListingImage.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ product: { type: Object, required: true } });

const ago = (iso) => {
    const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);
    return days <= 0 ? "Aujourd'hui" : days === 1 ? 'Hier' : `Il y a ${days} j`;
};
</script>

<template>
    <Link :href="`/marketplace/${product.slug}`" class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-lg">
        <div class="relative">
            <ListingImage :src="product.image_url" fallback-class="bg-pink-700 text-sm" class="h-40">{{ product.category_label ?? 'Annonce' }}</ListingImage>
            <span v-if="product.listing_type === 'recherche'" class="absolute left-2 top-2 rounded-full bg-navy-900 px-2 py-0.5 text-[11px] font-semibold text-white">Recherche</span>
            <span v-if="product.sold_at" class="absolute right-2 top-2 rounded-full bg-gray-800 px-2 py-0.5 text-[11px] font-semibold text-white">Vendu</span>
            <span v-if="product.gallery_urls?.length > 1" class="absolute bottom-2 right-2 rounded bg-black/60 px-1.5 py-0.5 text-[11px] text-white">📷 {{ product.gallery_urls.length }}</span>
        </div>
        <div class="p-3">
            <p class="line-clamp-2 text-sm font-semibold text-navy-900 group-hover:text-pink-700">{{ product.title }}</p>
            <p class="mt-1 font-bold text-navy-900">
                {{ Number(product.price) > 0 ? `${Number(product.price).toLocaleString('fr-FR')} ${product.currency}` : (product.listing_type === 'recherche' ? 'Budget a discuter' : 'Prix a discuter') }}
                <span v-if="product.negotiable" class="text-xs font-normal text-navy-500">· a debattre</span>
            </p>
            <p class="mt-1 text-xs text-navy-500">{{ product.city }}<span v-if="product.district"> · {{ product.district }}</span> · {{ ago(product.created_at) }}</p>
        </div>
    </Link>
</template>
