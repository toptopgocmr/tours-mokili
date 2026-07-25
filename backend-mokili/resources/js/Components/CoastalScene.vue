<script setup>
// Decorative "dream destination" illustration - fallback used only when
// no real photo is found (see DestinationImage.vue). Redesigned to
// mirror the reference mockup: a diagonal cliffside white village
// climbing toward the upper-right corner, deep blue sea/sky wash,
// a hazy island on the horizon, and a banking jet up top. Everything
// is percentage/viewBox-based (no fixed pixel sizes) so it scales
// cleanly from a 144px-tall listing card up to a 560px hero without
// the cropping or oversized-blob bugs the earlier versions had.
defineProps({
    class: { type: String, default: 'h-full w-full' },
});
</script>

<template>
    <div :class="[$props.class, 'relative overflow-hidden bg-gradient-to-br from-[#0B2A5B] via-[#2E5FA3] to-[#9FC8EA]']">
        <!-- hazy island on the horizon -->
        <div class="absolute left-[8%] top-[44%] h-[6%] w-[22%] min-h-[3px] rounded-full bg-[#5b7fa6] opacity-30 blur-md"></div>

        <!-- sea reflection lines -->
        <div class="absolute left-0 top-[58%] h-px w-[62%] bg-white opacity-10"></div>
        <div class="absolute left-0 top-[72%] h-px w-[55%] bg-white opacity-10"></div>

        <!-- banking jet + soft trail, upper right -->
        <svg viewBox="0 0 100 60" class="absolute right-[6%] top-[8%] h-[20%] w-[26%] min-h-[16px] min-w-[40px]" fill="none">
            <path d="M2 52 C30 50 46 34 60 18" stroke="#D4A017" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.85" />
            <g transform="translate(52,6) rotate(-24)">
                <ellipse cx="16" cy="4" rx="16" ry="3.4" fill="#F5F6F8" />
                <path d="M6 4 L-2 -6 L2 -6 L12 2 Z" fill="#D8DCE2" />
                <path d="M26 4 L34 -4 L38 -3 L30 5 Z" fill="#D8DCE2" />
                <path d="M2 3 L-4 9 L0 9 L6 4 Z" fill="#C7CCD4" />
            </g>
        </svg>

        <!-- cliffside village mass, climbing from bottom-right toward the
             top-right - one SVG so the terraces line up perfectly. -->
        <svg viewBox="0 0 300 200" preserveAspectRatio="none" class="absolute inset-0 h-full w-full">
            <!-- rock base -->
            <path d="M300,200 L130,200 L150,160 L180,130 L210,140 L235,100 L260,110 L280,70 L300,80 Z" fill="#6b5c47" />
            <!-- village terraces -->
            <path d="M300,200 L160,200 L175,155 L200,155 L215,120 L240,120 L255,90 L275,90 L290,60 L300,60 Z" fill="#F7F5F0" />
            <!-- windows / doors -->
            <g fill="#0B2A5B" opacity="0.5">
                <rect x="180" y="175" width="10" height="10" />
                <rect x="205" y="175" width="10" height="10" />
                <rect x="230" y="175" width="10" height="10" />
                <rect x="195" y="140" width="9" height="9" />
                <rect x="218" y="140" width="9" height="9" />
                <rect x="235" y="105" width="9" height="9" />
                <rect x="253" y="105" width="9" height="9" />
                <rect x="268" y="76" width="8" height="8" />
                <rect x="282" y="46" width="7" height="7" />
            </g>
            <!-- gold dome accent -->
            <circle cx="230" cy="112" r="6.5" fill="#D4A017" />
            <!-- small watchtower on the cliff edge -->
            <rect x="286" y="46" width="7" height="16" fill="#F7F5F0" />
            <path d="M286,46 L293,46 L289.5,38 Z" fill="#D4A017" />
        </svg>

        <!-- foreground ledge, bottom-right, for depth -->
        <div class="absolute bottom-0 right-0 h-[14%] w-[36%] rounded-tl-3xl bg-[#F7F5F0] opacity-90"></div>
    </div>
</template>
