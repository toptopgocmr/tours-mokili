<script setup>
// Logo de compagnie (Wikimedia Commons) ; a defaut, pastille avec le code IATA.
import { computed, ref, watch } from 'vue';
import { airlineLogoUrl } from '@/utils/airlines';

const props = defineProps({
    code: { type: String, default: '' },
    size: { type: String, default: 'h-10 w-10 text-xs' },
});

const PALETTE = ['bg-navy-600', 'bg-emerald-600', 'bg-rose-600', 'bg-amber-600', 'bg-sky-600', 'bg-violet-600', 'bg-teal-600'];
const color = computed(() => {
    const c = (props.code || '?').toUpperCase();
    return PALETTE[[...c].reduce((t, ch) => t + ch.charCodeAt(0), 0) % PALETTE.length];
});
const logo = computed(() => airlineLogoUrl(props.code, 120));
const broken = ref(false);
watch(() => props.code, () => (broken.value = false));
</script>

<template>
    <span v-if="logo && !broken" class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-white p-0.5" :class="size">
        <img :src="logo" :alt="code" class="max-h-full max-w-full object-contain" loading="lazy" @error="broken = true" />
    </span>
    <span v-else class="inline-flex shrink-0 items-center justify-center rounded-lg font-extrabold tracking-wide text-white" :class="[size, color]">
        {{ (code || '✈').toUpperCase().slice(0, 3) }}
    </span>
</template>
