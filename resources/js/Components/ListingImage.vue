<script setup>
// Shows the partner-uploaded (or demo) cover photo (listing.image_url,
// appended by each model's getImageUrlAttribute()) when one exists, falling
// back to a plain colored block with a caption - the same placeholder look
// these listing cards have always had, just no longer the *only* option.
//
// `failed` tracks a load error on the current `src` (e.g. a hotlinked demo
// photo URL that stops resolving) so the view degrades to the colored
// fallback instead of showing a broken-image icon.
import { ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, default: null },
    fallbackClass: { type: String, default: 'bg-navy-700' },
});

const failed = ref(false);
watch(() => props.src, () => { failed.value = false; });
</script>

<template>
    <div class="h-36 w-full overflow-hidden">
        <img
            v-if="src && !failed"
            :src="src"
            class="h-full w-full object-cover"
            loading="lazy"
            @error="failed = true"
        />
        <div v-else class="flex h-full items-center justify-center px-3 text-center text-white" :class="fallbackClass">
            <slot />
        </div>
    </div>
</template>
