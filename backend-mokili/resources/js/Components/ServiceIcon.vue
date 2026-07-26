<script setup>
// Flat pictograms matching the icon set used by the mobile app (Material
// Icons: flight, location_city, directions_car, confirmation_number,
// shopping_bag, local_shipping) so both clients look consistent.
// fill="currentColor" so each icon inherits its tile's brand color via
// the parent's :style="{ color: c.color }".
const props = defineProps({
    slug: { type: String, required: true },
    class: { type: String, default: 'h-7 w-7' },
});
</script>

<template>
    <!-- Voyage: plane, matching Material's "flight" glyph -->
    <svg v-if="slug === 'voyage'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"
        />
    </svg>

    <!-- Logement: city skyline, matching Material's "location_city" -->
    <svg v-else-if="slug === 'logement'" :class="props.class" viewBox="0 0 24 24">
        <g fill="currentColor">
            <rect x="3" y="10" width="5" height="11" />
            <rect x="9.5" y="5" width="5" height="16" />
            <rect x="16" y="12" width="5" height="9" />
        </g>
        <g fill="#ffffff">
            <rect x="4.2" y="12" width="1.2" height="1.2" />
            <rect x="6.6" y="12" width="1.2" height="1.2" />
            <rect x="4.2" y="15" width="1.2" height="1.2" />
            <rect x="6.6" y="15" width="1.2" height="1.2" />
            <rect x="4.2" y="18" width="1.2" height="1.2" />
            <rect x="6.6" y="18" width="1.2" height="1.2" />
            <rect x="10.7" y="7" width="1.2" height="1.2" />
            <rect x="13.1" y="7" width="1.2" height="1.2" />
            <rect x="10.7" y="10" width="1.2" height="1.2" />
            <rect x="13.1" y="10" width="1.2" height="1.2" />
            <rect x="10.7" y="13" width="1.2" height="1.2" />
            <rect x="13.1" y="13" width="1.2" height="1.2" />
            <rect x="10.7" y="16" width="1.2" height="1.2" />
            <rect x="13.1" y="16" width="1.2" height="1.2" />
            <rect x="17.2" y="14" width="1.2" height="1.2" />
            <rect x="19.6" y="14" width="1.2" height="1.2" />
            <rect x="17.2" y="17" width="1.2" height="1.2" />
            <rect x="19.6" y="17" width="1.2" height="1.2" />
        </g>
    </svg>

    <!-- Voiture: side-view car, matching Material's "directions_car" -->
    <svg v-else-if="slug === 'voiture'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M4 17v-3.5a2 2 0 0 1 .13-.71L5.6 9.1A2 2 0 0 1 7.47 8h9.06a2 2 0 0 1 1.87 1.1l1.47 3.69a2 2 0 0 1 .13.71V17a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1v-.5h-9V17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"
        />
        <path fill="#ffffff" d="M6.2 12.5 7.3 9.6a1 1 0 0 1 .93-.6h7.54a1 1 0 0 1 .93.6l1.1 2.9Z" opacity="0.9" />
        <circle cx="7.5" cy="16.3" r="1.6" fill="#ffffff" stroke="currentColor" stroke-width="1" />
        <circle cx="16.5" cy="16.3" r="1.6" fill="#ffffff" stroke="currentColor" stroke-width="1" />
    </svg>

    <!-- Divertissement: flat horizontal ticket, matching Material's "confirmation_number" -->
    <svg v-else-if="slug === 'divertissement'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-3a2 2 0 0 0 0-4Z"
        />
        <g fill="#ffffff">
            <rect x="11.2" y="7" width="1.6" height="1.8" />
            <rect x="11.2" y="10.6" width="1.6" height="1.8" />
            <rect x="11.2" y="14.2" width="1.6" height="1.8" />
        </g>
    </svg>

    <!-- Marketplace: shopping bag, matching Material's "shopping_bag" -->
    <svg v-else-if="slug === 'marketplace'" :class="props.class" viewBox="0 0 24 24">
        <path fill="currentColor" d="M6 8h12l-1.2 12.3a1 1 0 0 1-1 .7H8.2a1 1 0 0 1-1-.7L6 8Z" />
        <path fill="none" stroke="#ffffff" stroke-width="1.6" d="M9 8V6a3 3 0 0 1 6 0v2" />
    </svg>

    <!-- Fret: delivery truck, matching Material's "local_shipping" -->
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
