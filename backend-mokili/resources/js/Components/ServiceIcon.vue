<script setup>
// Flat, solid-fill pictograms matching the reference artwork exactly
// (filled silhouettes with white cutout details, not thin outlines).
// stroke/fill="currentColor" so each icon inherits its tile's brand
// color via the parent's :style="{ color: c.color }".
const props = defineProps({
    slug: { type: String, required: true },
    class: { type: String, default: 'h-7 w-7' },
});
</script>

<template>
    <svg v-if="slug === 'voyage'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            transform="rotate(15 12 12)"
            d="M22 16v-2l-8.5-5V3.5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5V9L2 14v2l8.5-2.5V19L8 20.5V22l4-1 4 1v-1.5L13.5 19v-5.5L22 16z"
        />
    </svg>

    <svg v-else-if="slug === 'logement'" :class="props.class" viewBox="0 0 24 24">
        <rect x="7" y="3" width="10" height="18" rx="1" fill="currentColor" />
        <rect x="10.5" y="1" width="3" height="2.5" fill="currentColor" />
        <g fill="#ffffff">
            <rect x="9" y="6.5" width="2" height="2" />
            <rect x="13" y="6.5" width="2" height="2" />
            <rect x="9" y="10.5" width="2" height="2" />
            <rect x="13" y="10.5" width="2" height="2" />
            <rect x="9" y="14.5" width="2" height="2" />
            <rect x="13" y="14.5" width="2" height="2" />
        </g>
        <rect x="10" y="18.5" width="4" height="2.5" fill="#ffffff" opacity="0.85" />
    </svg>

    <svg v-else-if="slug === 'voiture'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M5 16.2 6.4 10.6A2 2 0 0 1 8.4 9h7.2a2 2 0 0 1 2 1.6l1.4 5.6V19a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1H8v1a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-2.8Z"
        />
        <circle cx="7.5" cy="16" r="1.6" fill="#ffffff" stroke="currentColor" stroke-width="1" />
        <circle cx="16.5" cy="16" r="1.6" fill="#ffffff" stroke="currentColor" stroke-width="1" />
    </svg>

    <svg v-else-if="slug === 'divertissement'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 15.5 15.5 4a1.8 1.8 0 0 1 2.5 0l2 2a1.8 1.8 0 0 1 0 2.5L8.5 20a1.8 1.8 0 0 1-2.5 0l-2-2a1.8 1.8 0 0 1 0-2.5Z" />
        <path d="M12.5 7 17 11.5" stroke-dasharray="1.6 1.6" />
    </svg>

    <svg v-else-if="slug === 'marketplace'" :class="props.class" viewBox="0 0 24 24">
        <path fill="currentColor" d="M6 8h12l-1.2 12.3a1 1 0 0 1-1 .7H8.2a1 1 0 0 1-1-.7L6 8Z" />
        <path fill="none" stroke="#ffffff" stroke-width="1.6" d="M9 8V6a3 3 0 0 1 6 0v2" />
    </svg>

    <svg v-else-if="slug === 'fret'" :class="props.class" viewBox="0 0 24 24">
        <path fill="currentColor" d="M2 7h11v9H2z" />
        <path fill="currentColor" d="M13 10h4.3a1 1 0 0 1 .8.4l2.2 2.9a1 1 0 0 1 .2.6V16h-7.5z" />
        <circle cx="7" cy="18" r="1.7" fill="#ffffff" stroke="currentColor" stroke-width="1.1" />
        <circle cx="17.5" cy="18" r="1.7" fill="#ffffff" stroke="currentColor" stroke-width="1.1" />
    </svg>

    <svg v-else :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="9" />
    </svg>
</template>
