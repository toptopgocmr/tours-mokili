<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ products: { type: Object, required: true } });

const STATUS = {
    draft: ['Brouillon', 'bg-gray-100 text-gray-700'],
    pending: ['En attente de validation', 'bg-amber-100 text-amber-800'],
    published: ['En ligne', 'bg-green-100 text-green-800'],
    rejected: ['Refusee', 'bg-red-100 text-red-800'],
};
const sold = (p) => router.post(`/marketplace/annonce/${p.id}/vendu`, {}, { preserveScroll: true });
const remove = (p) => {
    if (confirm(`Supprimer l'annonce « ${p.title} » ?`)) router.delete(`/marketplace/annonce/${p.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Mes annonces" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">Mes annonces</h1>
                <p class="text-sm text-navy-600">Gerez vos annonces Marketplace.</p>
            </div>
            <Link href="/marketplace/deposer" class="btn-gold">+ Deposer une annonce</Link>
        </div>
        <div class="mt-6 space-y-3">
            <div v-for="p in products.data" :key="p.id" class="flex flex-wrap items-center gap-4 rounded-2xl border p-3">
                <div class="h-20 w-24 shrink-0 overflow-hidden rounded-lg">
                    <ListingImage :src="p.image_url" fallback-class="bg-pink-700 text-xs" class="h-20">{{ p.category_label }}</ListingImage>
                </div>
                <div class="min-w-0 flex-1">
                    <Link :href="`/marketplace/${p.slug}`" class="font-semibold text-navy-900 hover:underline">{{ p.title }}</Link>
                    <p class="text-sm text-navy-600">{{ Number(p.price).toLocaleString('fr-FR') }} {{ p.currency }} · {{ p.city }} · {{ p.views }} vue(s)</p>
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="STATUS[p.status]?.[1]">{{ STATUS[p.status]?.[0] }}</span>
                    <span v-if="p.sold_at" class="ml-2 inline-block rounded-full bg-gray-800 px-2 py-0.5 text-xs font-semibold text-white">Vendu</span>
                    <p v-if="p.status === 'rejected' && p.rejection_reason" class="text-xs text-red-700">Motif : {{ p.rejection_reason }}</p>
                </div>
                <div class="flex gap-3 text-sm">
                    <Link :href="`/marketplace/annonce/${p.id}/modifier`" class="font-semibold text-navy-900 underline">Modifier</Link>
                    <button v-if="p.status === 'published'" type="button" class="font-semibold text-navy-900 underline" @click="sold(p)">{{ p.sold_at ? 'Remettre en ligne' : 'Vendu' }}</button>
                    <button type="button" class="font-semibold text-red-600 underline" @click="remove(p)">Supprimer</button>
                </div>
            </div>
            <p v-if="!products.data.length" class="rounded-2xl border p-10 text-center text-navy-500">Vous n'avez pas encore d'annonce.</p>
        </div>
        <Paginator :page="products" />
    </div>
</template>
