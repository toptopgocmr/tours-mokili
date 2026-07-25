<script setup>
// Hand-drawn outline icons (no external icon package) matching the
// 6 pictograms from the MOKILI TOUR artwork: Voyage (avion), Logement
// (immeuble), Voiture, Divertissement (billet), Marketplace (sac),
// Fret (camion). One icon per service.slug, always stroke="currentColor"
// so it inherits the tile's brand color.
const props = defineProps({
    slug: { type: String, required: true },
    class: { type: String, default: 'h-7 w-7' },
});
</script>

<template>
    <svg v-if="slug === 'voyage'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.5 3.5 3 12l3.5.5 2 3.5.9-3.4L21 4.5c.4-1-.5-2-1.5-1.5L10.5 8" />
        <path d="M9.5 8 3 12" />
    </svg>

    <svg v-else-if="slug === 'logement'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 21V7l6-4 6 4v14" />
        <path d="M14 21v-8h4v8" />
        <path d="M8 9h.01M12 9h.01M8 13h.01M12 13h.01M8 17h.01" />
    </svg>

    <svg v-else-if="slug === 'voiture'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 16v-3.5L5 8h14l2 4.5V16" />
        <path d="M3 16h18" />
        <path d="M5 16v2M19 16v2" />
        <circle cx="7" cy="16" r="1.6" />
        <circle cx="17" cy="16" r="1.6" />
        <path d="M6 12h12" />
    </svg>

    <svg v-else-if="slug === 'divertissement'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 9a2 2 0 0 1 0-4h16a2 2 0 0 1 0 4 2 2 0 0 0 0 6 2 2 0 0 1 0 4H4a2 2 0 0 1 0-4 2 2 0 0 0 0-6Z" />
        <path d="M13 5v14" stroke-dasharray="2 2" />
    </svg>

    <svg v-else-if="slug === 'marketplace'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8h12l-1 12H7L6 8Z" />
        <path d="M9 8V6a3 3 0 0 1 6 0v2" />
    </svg>

    <svg v-else-if="slug === 'fret'" :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7h11v9H3z" />
        <path d="M14 10h4l3 3v3h-7z" />
        <circle cx="7" cy="18" r="1.6" />
        <circle cx="17.5" cy="18" r="1.6" />
    </svg>

    <svg v-else :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="9" />
    </svg>
</template>
