<script setup>
// Tries a real photo first (public/images/destinations/<slug>.jpg -
// e.g. paris.jpg, dubai.jpg, matching the city name), and silently
// falls back to the CoastalScene illustration if that file doesn't
// exist (404 on <img> triggers @error). This means dropping a real
// photo into that folder is the entire integration - no code change,
// no rebuild needed, just refresh the page.
import { ref, watch } from 'vue';
import CoastalScene from '@/Components/CoastalScene.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    city: { type: String, required: true },
    class: { type: String, default: 'h-full w-full' },
});

const failed = ref(false);
watch(() => props.city, () => { failed.value = false; });
</script>

<template>
    <div :class="[props.class, 'relative overflow-hidden']">
        <img
            v-if="!failed"
            :src="`/images/destinations/${slugify(city)}.jpg`"
            :alt="city"
            class="h-full w-full object-cover"
            @error="failed = true"
        />
        <CoastalScene v-else class="h-full w-full" />
    </div>
</template>
