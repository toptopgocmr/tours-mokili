<script setup>
defineProps({
    operator: { type: Object, required: true },
    size: { type: String, default: 'md' }, // 'sm' | 'md'
    clickable: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
    dimmed: { type: Boolean, default: false },
});

defineEmits(['click']);
</script>

<template>
    <component
        :is="clickable ? 'button' : 'span'"
        :type="clickable ? 'button' : undefined"
        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 font-semibold transition"
        :class="[
            size === 'sm' ? 'h-6 text-[11px]' : 'h-9 text-xs',
            selected ? 'border-[#0972D3] ring-2 ring-[#0972D3]/40' : 'border-slate-200',
            dimmed ? 'opacity-35 grayscale' : '',
            clickable ? 'cursor-pointer hover:opacity-90' : '',
        ]"
        :style="!operator.logo ? { backgroundColor: operator.color, color: operator.text } : {}"
        @click="clickable && $emit('click')"
    >
        <img v-if="operator.logo" :src="operator.logo" :alt="operator.name" class="h-full w-auto object-contain" />
        <span v-else>{{ operator.name }}</span>
    </component>
</template>
