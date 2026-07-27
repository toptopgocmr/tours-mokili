<script setup>
// Shows the partner-uploaded cover photo (listing.image_url, appended by
// each model's getImageUrlAttribute()) when one exists, falling back to a
// plain colored block with a caption - the same placeholder look these
// listing cards have always had, just no longer the *only* option.
defineProps({
    src: { type: String, default: null },
    fallbackClass: { type: String, default: 'bg-navy-700' },
});
</script>

<template>
    <div class="h-36 w-full overflow-hidden">
        <img v-if="src" :src="src" class="h-full w-full object-cover" loading="lazy" />
        <div v-else class="flex h-full items-center justify-center px-3 text-center text-white" :class="fallbackClass">
            <slot />
        </div>
    </div>
</template>
