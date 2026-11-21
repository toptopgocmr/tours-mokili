<script setup>
// Vignette d'une societe de l'annuaire : photo officielle, sinon photo
// d'illustration (mention discrete), sinon initiales si l'image ne charge pas.
import { computed, ref } from 'vue';

const props = defineProps({
    business: { type: Object, required: true },
    size: { type: String, default: 'h-16 w-24' },
});

const failed = ref(false);
const src = computed(() => (failed.value ? null : props.business.image_url));
const initials = computed(() =>
    (props.business.name ?? '?')
        .replace(/[^\p{L}\p{N} ]/gu, ' ')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join(''),
);
</script>

<template>
    <figure :class="size" class="relative shrink-0 overflow-hidden rounded-lg bg-navy-50 ring-1 ring-black/5">
        <img
            v-if="src"
            :src="src"
            :alt="business.name"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="h-full w-full object-cover"
            @error="failed = true"
        />
        <span v-else class="flex h-full w-full items-center justify-center text-lg font-bold text-navy-700">{{ initials }}</span>
        <figcaption
            v-if="src && business.image_is_illustration"
            class="absolute inset-x-0 bottom-0 bg-black/55 px-1 py-px text-center text-[9px] leading-tight text-white"
        >Photo d'illustration</figcaption>
    </figure>
</template>
