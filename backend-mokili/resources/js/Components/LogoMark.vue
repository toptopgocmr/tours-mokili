<script setup>
// Real MOKILI TOUR logo. Two source files:
// - public/images/logo.jpg: original, opaque white background - used on
//   light surfaces (navbar, white cards).
// - public/images/logo-transparent.png: same logo with the white
//   background keyed out to transparency - used on dark surfaces
//   (footer, navy hero) so it blends in directly instead of sitting in
//   a visible white box/chip.
defineProps({
    variant: { type: String, default: 'dark' }, // 'dark' = light background (navbar, cards) | 'light' = dark background (footer, hero)
    size: { type: String, default: 'h-12' },
});
</script>

<template>
    <img
        v-if="variant === 'light'"
        src="/images/logo-transparent.png"
        alt="MOKILI TOUR"
        :class="[size, 'w-auto object-contain']"
    />
    <img v-else src="/images/logo.jpg" alt="MOKILI TOUR" :class="[size, 'w-auto object-contain']" />
</template>
