<script setup>
// Pastille de compagnie (code IATA sur fond colore) : pas d'image externe.
import { computed } from 'vue';

const props = defineProps({
    code: { type: String, default: '' },
    size: { type: String, default: 'h-10 w-10 text-xs' },
});

const PALETTE = ['bg-navy-600', 'bg-emerald-600', 'bg-rose-600', 'bg-amber-600', 'bg-sky-600', 'bg-violet-600', 'bg-teal-600'];
const color = computed(() => {
    const c = (props.code || '?').toUpperCase();
    return PALETTE[[...c].reduce((t, ch) => t + ch.charCodeAt(0), 0) % PALETTE.length];
});
</script>

<template>
    <span class="inline-flex shrink-0 items-center justify-center rounded-lg font-extrabold tracking-wide text-white" :class="[size, color]">
        {{ (code || '✈').toUpperCase().slice(0, 3) }}
    </span>
</template>
