<script setup>
// Decorative "dream destination" illustration - fallback used only
// when no real photo is found (see DestinationImage.vue). Fixed brand
// palette (no hue-rotate - that was making cards look purple/green
// and washing out the buildings to grey). Layered bands, each pinned
// to top/bottom with a fixed percentage height so it never stretches
// or crops badly regardless of the container's aspect ratio.
defineProps({
    class: { type: String, default: 'h-full w-full' },
});
</script>

<template>
    <div :class="[$props.class, 'relative overflow-hidden bg-gradient-to-b from-[#0B2A5B] via-[#1E4F9C] to-[#7FB2E5]']">
        <!-- sun glow -->
        <div class="absolute right-[10%] top-[12%] h-24 w-24 rounded-full bg-gold-400 opacity-60 blur-2xl"></div>

        <!-- clouds -->
        <div class="absolute left-[10%] top-[20%] h-3 w-16 rounded-full bg-white opacity-40"></div>
        <div class="absolute left-[16%] top-[27%] h-2 w-10 rounded-full bg-white opacity-30"></div>
        <div class="absolute left-[45%] top-[16%] h-2.5 w-12 rounded-full bg-white opacity-25"></div>

        <!-- plane + gold trail -->
        <svg viewBox="0 0 100 44" class="absolute right-[8%] top-[6%] h-10 w-24" fill="none">
            <path d="M4 34 C36 34 54 16 82 8" stroke="#D4A017" stroke-width="3" fill="none" stroke-linecap="round" />
            <path d="M70 2 L94 4 L76 18 L72 12 Z" fill="#D4A017" />
        </svg>

        <!-- sea -->
        <div class="absolute bottom-0 left-0 right-0 h-[28%] bg-gradient-to-b from-[#144a8c] to-[#081E42]">
            <div class="absolute left-0 top-[30%] h-px w-full bg-white opacity-10"></div>
            <div class="absolute left-0 top-[62%] h-px w-full bg-white opacity-10"></div>
        </div>

        <!-- cliff base, grounds the village so it doesn't float -->
        <div class="absolute bottom-[26%] left-0 h-[6%] w-full bg-[#6b5138]"></div>

        <!-- whitewashed village silhouette, gold domes to match the brand -->
        <svg viewBox="0 0 700 100" preserveAspectRatio="none" class="absolute bottom-[30%] left-0 h-[20%] w-full">
            <g fill="#ffffff">
                <rect x="0" y="30" width="95" height="70" />
                <rect x="105" y="10" width="80" height="90" />
                <rect x="195" y="35" width="100" height="65" />
                <rect x="305" y="5" width="85" height="95" />
                <rect x="400" y="28" width="95" height="72" />
                <rect x="505" y="15" width="90" height="85" />
                <rect x="605" y="32" width="95" height="68" />
            </g>
            <g fill="#D4A017">
                <circle cx="47" cy="24" r="18" />
                <circle cx="145" cy="6" r="15" />
                <circle cx="345" cy="1" r="16" />
                <circle cx="550" cy="10" r="16" />
            </g>
            <g fill="#0B2A5B" opacity="0.55">
                <rect x="25" y="55" width="14" height="14" />
                <rect x="55" y="55" width="14" height="14" />
                <rect x="130" y="45" width="14" height="14" />
                <rect x="230" y="55" width="14" height="14" />
                <rect x="262" y="55" width="14" height="14" />
                <rect x="335" y="45" width="14" height="14" />
                <rect x="430" y="55" width="14" height="14" />
                <rect x="462" y="55" width="14" height="14" />
                <rect x="535" y="45" width="14" height="14" />
                <rect x="635" y="55" width="14" height="14" />
            </g>
        </svg>
    </div>
</template>
