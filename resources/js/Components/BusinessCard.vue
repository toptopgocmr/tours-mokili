<script setup>
import GuideCover from '@/Components/GuideCover.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ business: { type: Object, required: true } });
</script>

<template>
    <Link :href="`/partenaires/${business.slug}`" class="group overflow-hidden rounded-2xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
        <div class="relative h-36">
            <GuideCover :src="business.image_url" :category="business.category" :alt="business.name" icon-class="h-10 w-10" />
        </div>
        <div class="p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600">{{ business.category_label }}</p>
            <p class="font-semibold text-navy-900 group-hover:text-gold-600">{{ business.name }}</p>
            <p class="text-xs text-navy-500">{{ business.district ?? business.city }}<span v-if="business.price_range"> · {{ business.price_range }}</span></p>
        </div>
    </Link>
</template>
