<script setup>
// Three-tier fallback: (1) the offer's own uploaded photo (image_url from
// the model) if the partner/admin attached one, (2) a real photo dropped at
// public/images/destinations/<city-slug>.jpg by convention, (3) the
// CoastalScene illustration if neither exists. Each tier is just an <img>
// @error falling through to the next - no code change needed when a photo
// is added later.
import { ref, watch } from 'vue';
import CoastalScene from '@/Components/CoastalScene.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    city: { type: String, required: true },
    image: { type: String, default: null },
    class: { type: String, default: 'h-full w-full' },
});

const stage = ref(props.image ? 'uploaded' : 'convention');
watch(() => [props.city, props.image], () => {
    stage.value = props.image ? 'uploaded' : 'convention';
});
</script>

<template>
    <div :class="[props.class, 'relative overflow-hidden']">
        <img
            v-if="stage === 'uploaded'"
            :src="image"
            :alt="city"
            class="h-full w-full object-cover"
            @error="stage = 'convention'"
        />
        <img
            v-else-if="stage === 'convention'"
            :src="`/images/destinations/${slugify(city)}.jpg`"
            :alt="city"
            class="h-full w-full object-cover"
            @error="stage = 'illustration'"
        />
        <CoastalScene v-else class="h-full w-full" />
    </div>
</template>
