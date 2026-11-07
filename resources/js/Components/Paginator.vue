<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ page: { type: Object, required: true } });
</script>

<template>
    <div v-if="page.last_page > 1" class="mt-10 flex flex-wrap justify-center gap-2">
        <Link
            v-for="l in page.links"
            :key="l.label"
            :href="l.url ?? ''"
            preserve-scroll
            class="rounded-lg border px-3 py-1.5 text-sm"
            :class="[l.active ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700', !l.url && 'pointer-events-none opacity-40']"
            v-html="l.label"
        />
    </div>
</template>
