<script setup>
// Carte d'un parc d'attractions / parc aquatique / zoo (rubrique Divertissement).
import ListingImage from '@/Components/ListingImage.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ park: { type: Object, required: true } });

const categoryColors = {
    parc_attractions: 'bg-amber-500',
    parc_aquatique: 'bg-sky-600',
    zoo: 'bg-emerald-700',
    aire_de_jeux: 'bg-pink-600',
    ile_loisirs: 'bg-cyan-700',
    parc_naturel: 'bg-green-800',
};

const price = (p) =>
    p.is_bookable ? `${Number(p.price_adult).toLocaleString('fr-FR')} ${p.currency}` : 'Tarif sur place';
</script>

<template>
    <Link
        :href="`/divertissement/parcs/${park.slug}`"
        class="group overflow-hidden rounded-2xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
    >
        <div class="relative">
            <ListingImage :src="park.image_url" :fallback-class="categoryColors[park.category] ?? 'bg-navy-700'">
                <span class="text-lg font-semibold">{{ park.name }}</span>
            </ListingImage>
            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-semibold text-navy-900">{{ park.category_label }}</span>
            <span v-if="park.is_featured" class="absolute right-3 top-3 rounded-full bg-gold-500 px-2.5 py-0.5 text-xs font-semibold text-navy-900">Coup de coeur</span>
        </div>
        <div class="p-4">
            <p class="font-semibold text-navy-900 group-hover:text-gold-600">{{ park.name }}</p>
            <p class="mt-0.5 text-sm text-navy-500">{{ park.city }}<span v-if="park.district"> · {{ park.district }}</span></p>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-sm font-semibold" :class="park.is_bookable ? 'text-gold-600' : 'text-navy-400'">
                    <span v-if="park.is_bookable" class="font-normal text-navy-500">Des </span>{{ price(park) }}
                </p>
                <span class="text-xs text-navy-500">{{ park.opening_hours }}</span>
            </div>
        </div>
    </Link>
</template>
