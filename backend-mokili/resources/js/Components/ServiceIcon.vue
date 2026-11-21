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

    <!-- Voiture : icone Material "directions_car" (meme famille que l'avion et le chariot). -->
    <svg v-else-if="slug === 'voiture'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"
        />
    </svg>

    <!-- Tourisme (slug "divertissement") : trois grandes roues de manege
         (une grande au centre, deux petites de chaque cote) sur un socle. -->
    <svg v-else-if="slug === 'divertissement'" :class="props.class" viewBox="0 0 24 24">
        <circle cx="4.6" cy="12.6" r="3.4" fill="none" stroke="currentColor" stroke-width="1.0" />
        <line x1="8.00" y1="12.60" x2="1.20" y2="12.60" stroke="currentColor" stroke-width="0.5" />
        <line x1="7.00" y1="15.00" x2="2.20" y2="10.20" stroke="currentColor" stroke-width="0.5" />
        <line x1="4.60" y1="16.00" x2="4.60" y2="9.20" stroke="currentColor" stroke-width="0.5" />
        <line x1="2.20" y1="15.00" x2="7.00" y2="10.20" stroke="currentColor" stroke-width="0.5" />
        <rect x="3.80" y="9.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="6.20" y="10.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="7.20" y="12.80" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="6.20" y="15.20" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="1.40" y="15.20" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="0.40" y="12.80" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="1.40" y="10.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <circle cx="4.6" cy="12.6" r="0.68" fill="currentColor" />
        <path d="M4.6 12.6 2.40 20.6h0.88L4.6 14.64l1.32 5.96h0.88z" fill="currentColor" />
        <circle cx="19.4" cy="12.6" r="3.4" fill="none" stroke="currentColor" stroke-width="1.0" />
        <line x1="22.80" y1="12.60" x2="16.00" y2="12.60" stroke="currentColor" stroke-width="0.5" />
        <line x1="21.80" y1="15.00" x2="17.00" y2="10.20" stroke="currentColor" stroke-width="0.5" />
        <line x1="19.40" y1="16.00" x2="19.40" y2="9.20" stroke="currentColor" stroke-width="0.5" />
        <line x1="17.00" y1="15.00" x2="21.80" y2="10.20" stroke="currentColor" stroke-width="0.5" />
        <rect x="18.60" y="9.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="21.00" y="10.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="22.00" y="12.80" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="21.00" y="15.20" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="16.20" y="15.20" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="15.20" y="12.80" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <rect x="16.20" y="10.40" width="1.6" height="1.2" rx="0.36" fill="currentColor" />
        <circle cx="19.4" cy="12.6" r="0.68" fill="currentColor" />
        <path d="M19.4 12.6 17.20 20.6h0.88L19.4 14.64l1.32 5.96h0.88z" fill="currentColor" />
        <circle cx="12" cy="8.2" r="5.6" fill="none" stroke="currentColor" stroke-width="1.3" />
        <line x1="17.60" y1="8.20" x2="6.40" y2="8.20" stroke="currentColor" stroke-width="0.7" />
        <line x1="15.96" y1="12.16" x2="8.04" y2="4.24" stroke="currentColor" stroke-width="0.7" />
        <line x1="12.00" y1="13.80" x2="12.00" y2="2.60" stroke="currentColor" stroke-width="0.7" />
        <line x1="8.04" y1="12.16" x2="15.96" y2="4.24" stroke="currentColor" stroke-width="0.7" />
        <rect x="10.90" y="2.80" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="14.86" y="4.44" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="16.50" y="8.40" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="14.86" y="12.36" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="6.94" y="12.36" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="5.30" y="8.40" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <rect x="6.94" y="4.44" width="2.2" height="1.7" rx="0.51" fill="currentColor" />
        <circle cx="12" cy="8.2" r="1.12" fill="currentColor" />
        <path d="M12 8.2 8.40 20.6h1.44L12 11.56l2.16 9.04h1.44z" fill="currentColor" />
        <rect x="0.8" y="20.4" width="22.4" height="1.8" rx="0.9" fill="currentColor" />
    </svg>

    <!-- Marketplace: shopping bag - the EXACT Material Icons "shopping_bag"
         glyph (previous version was a hand-drawn trapezoid + thin handle
         stroke that read as a shapeless blob at tile size; this is the
         real, well-tested icon path so it's unmistakably a bag). -->
    <!-- Marketplace : chariot de courses (meme style plat que les autres icones) -->
    <svg v-else-if="slug === 'marketplace'" :class="props.class" viewBox="0 0 24 24">
        <path
            fill="currentColor"
            d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"
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
