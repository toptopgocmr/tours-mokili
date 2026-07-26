<script setup>
defineProps({
    operator: { type: Object, required: true },
    size: { type: String, default: 'md' }, // 'sm' | 'md'
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 font-semibold"
        :class="size === 'sm' ? 'h-6 text-[11px]' : 'h-8 text-xs'"
        :style="!operator.logo ? { backgroundColor: operator.color, color: operator.text } : {}"
    >
        <img v-if="operator.logo" :src="operator.logo" :alt="operator.name" class="h-full w-auto object-contain" />
        <span v-else>{{ operator.name }}</span>
    </span>
</template>
