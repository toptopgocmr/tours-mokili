<script setup>
// Flat pictograms matching the icon set used by the mobile app (Material
// Icons: flight, location_city, directions_car, attractions (grande roue
// pour Tourisme), shopping_bag, local_shipping) so both clients look consistent.
// fill="currentColor" so each icon inherits its tile's brand color via
// the parent's :style="{ color: c.color }".
//
// `accent` is the color used for the small cutout details (windows,
// dashed lines, handle strokes, wheel rims) that give each icon its
// shape. It defaults to white, which reads fine when the icon body is a
// brand color (voyage blue, logement green, etc. on a white card). When
// the icon itself is rendered white (e.g. on a solid navy background,
// where Voyage's own brand color IS navy and would vanish), pass an
// `accent` that contrasts with white instead - e.g. the same navy as
// the background, so the cutouts read as "holes" revealing it.
const props = defineProps({
    slug: { type: String, required: true },
    class: { type: String, default: 'h-7 w-7' },
    accent: { type: String, default: '#ffffff' },
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
        <g :fill="accent">
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

    <!-- Voiture: Material "directions_car" (voiture vue de face, phares et roues). -->
    <svg v-else-if="slug === 'voiture'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"
        />
    </svg>

    <!-- Tourisme (slug "divertissement") : grande roue de manege avec
         nacelles, sur son pied. -->
    <svg v-else-if="slug === 'divertissement'" :class="props.class" viewBox="0 0 24 24">
        <circle cx="12" cy="9.5" r="6.6" fill="none" stroke="currentColor" stroke-width="1.6" />
        <g stroke="currentColor" stroke-width="1">
            <line x1="12" y1="2.9" x2="12" y2="16.1" />
            <line x1="5.4" y1="9.5" x2="18.6" y2="9.5" />
            <line x1="7.33" y1="4.83" x2="16.67" y2="14.17" />
            <line x1="16.67" y1="4.83" x2="7.33" y2="14.17" />
        </g>
        <g fill="currentColor">
            <!-- nacelles -->
            <rect x="10.7" y="1.2" width="2.6" height="2.2" rx="0.7" />
            <rect x="17.4" y="8.4" width="2.6" height="2.2" rx="0.7" />
            <rect x="4" y="8.4" width="2.6" height="2.2" rx="0.7" />
            <rect x="15.4" y="3.6" width="2.4" height="2" rx="0.6" />
            <rect x="6.2" y="3.6" width="2.4" height="2" rx="0.6" />
            <rect x="15.4" y="13.2" width="2.4" height="2" rx="0.6" />
            <rect x="6.2" y="13.2" width="2.4" height="2" rx="0.6" />
            <circle cx="12" cy="9.5" r="1.5" />
            <!-- pied et socle -->
            <path d="M12 9.5 7.4 20.5h1.9L12 13.8l2.7 6.7h1.9z" />
            <rect x="5" y="20.4" width="14" height="1.8" rx="0.9" />
        </g>
    </svg>

    <!-- Marketplace: shopping bag - the EXACT Material Icons "shopping_bag"
         glyph (previous version was a hand-drawn trapezoid + thin handle
         stroke that read as a shapeless blob at tile size; this is the
         real, well-tested icon path so it's unmistakably a bag). -->
    <svg v-else-if="slug === 'marketplace'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm6 16H6V8h2v2c0 .55.45 1 1 1s1-.45 1-1V8h4v2c0 .55.45 1 1 1s1-.45 1-1V8h2v12z"
        />
    </svg>

    <!-- Fret : camion, navire porte-conteneurs et grue portuaire
         (transport routier, maritime et logistique). -->
    <svg v-else-if="slug === 'fret'" :class="props.class" viewBox="0 0 24 24">
        <g fill="currentColor">
            <!-- grue portuaire -->
            <rect x="18.3" y="3" width="1.4" height="12.5" />
            <path d="M12.5 3.4h11v1.3h-11z" />
            <path d="M19 1.2 23.3 3.5h-1.6L19 2.1 16.3 3.5h-1.6z" />
            <rect x="17" y="15" width="4" height="1.1" rx="0.3" />
            <rect x="14.2" y="4.6" width="0.5" height="3.2" />
            <path d="M13.6 7.6h1.7v0.9h-1.7z" />
            <!-- navire et conteneurs -->
            <path d="M8.6 15.4h13.2l-1.9 3.6H10.4z" />
            <rect x="10" y="12.6" width="2.5" height="2.4" rx="0.2" />
            <rect x="12.8" y="12.6" width="2.5" height="2.4" rx="0.2" />
            <rect x="15.6" y="12.6" width="2.3" height="2.4" rx="0.2" />
            <rect x="11.4" y="10" width="2.5" height="2.3" rx="0.2" />
            <rect x="14.2" y="10" width="2.5" height="2.3" rx="0.2" />
            <!-- camion -->
            <rect x="0.6" y="11.2" width="5.6" height="5" rx="0.4" />
            <path d="M6.6 12.6h1.6a.6.6 0 0 1 .5.3l1 1.6v1.7H6.6z" />
            <circle cx="2.4" cy="17" r="1.25" />
            <circle cx="7.6" cy="17" r="1.25" />
        </g>
        <!-- vagues -->
        <path d="M8.5 20.3c1 0 1 .7 2 .7s1-.7 2-.7 1 .7 2 .7 1-.7 2-.7 1 .7 2 .7 1-.7 2-.7" fill="none" stroke="currentColor" stroke-width="0.8" stroke-linecap="round" />
        <g :fill="accent">
            <path d="M7.2 13.2h.9l.7 1.2H7.2z" />
            <circle cx="2.4" cy="17" r="0.45" />
            <circle cx="7.6" cy="17" r="0.45" />
        </g>
    </svg>

    <!-- Loisirs & Parcs: ferris wheel (grande roue) -->
    <svg v-else-if="slug === 'loisirs'" :class="props.class" viewBox="0 0 24 24">
        <circle cx="12" cy="10" r="7.2" fill="none" stroke="currentColor" stroke-width="1.8" />
        <g stroke="currentColor" stroke-width="1.1">
            <line x1="12" y1="2.8" x2="12" y2="17.2" />
            <line x1="4.8" y1="10" x2="19.2" y2="10" />
            <line x1="6.9" y1="4.9" x2="17.1" y2="15.1" />
            <line x1="17.1" y1="4.9" x2="6.9" y2="15.1" />
        </g>
        <g fill="currentColor">
            <circle cx="12" cy="2.8" r="1.5" />
            <circle cx="19.2" cy="10" r="1.5" />
            <circle cx="12" cy="17.2" r="1.5" />
            <circle cx="4.8" cy="10" r="1.5" />
            <circle cx="12" cy="10" r="1.6" />
            <path d="M12 10 7.5 21.5h2.1L12 15l2.4 6.5h2.1z" />
        </g>
    </svg>

    <svg v-else :class="props.class" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="9" />
    </svg>
</template>
