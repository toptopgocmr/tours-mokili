<script setup>
// Real MOKILI TOUR logo (public/images/logo.jpg). The source file has
// a plain white background (JPG, no alpha), which reads fine on light
// surfaces (navbar, white cards) but needs a light chip behind it to
// stay legible on dark surfaces (footer, admin login) - see "light"
// variant below.
defineProps({
    variant: { type: String, default: 'dark' }, // 'dark' = light background (navbar, cards) | 'light' = dark background (footer)
    size: { type: String, default: 'h-12' },
});
</script>

<template>
    <div v-if="variant === 'light'" class="inline-block rounded-xl bg-white px-3 py-2 shadow-sm">
        <img src="/images/logo.jpg" alt="MOKILI TOUR" :class="[size, 'w-auto object-contain']" />
    </div>
    <img v-else src="/images/logo.jpg" alt="MOKILI TOUR" :class="[size, 'w-auto object-contain']" />
</template>
