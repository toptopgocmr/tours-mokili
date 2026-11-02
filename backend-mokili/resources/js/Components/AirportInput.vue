<script setup>
// Champ aeroport avec autocompletion (ville, code IATA, nom, pays).
import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Ville ou aeroport' },
    initialLabel: { type: String, default: '' },
    suggestions: { type: Array, default: () => [] },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'picked']);

const text = ref(props.initialLabel || props.modelValue || '');
const results = ref([]);
const open = ref(false);
const highlighted = ref(0);
let timer = null;

watch(() => props.initialLabel, (v) => { if (v && !text.value) text.value = v; });

const fetch = (q) => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/voyage/vols/aeroports', { params: { q } });
            results.value = data;
            highlighted.value = 0;
        } catch {
            results.value = [];
        }
    }, 180);
};

const onInput = () => {
    open.value = true;
    emit('update:modelValue', '');
    if (text.value.trim().length >= 1) fetch(text.value.trim());
    else results.value = props.suggestions;
};

const onFocus = () => {
    open.value = true;
    if (!results.value.length) results.value = props.suggestions.length ? props.suggestions : [];
    if (!results.value.length) fetch('');
};

const pick = (a) => {
    text.value = `${a.city} (${a.iata_code})`;
    emit('update:modelValue', a.iata_code);
    emit('picked', a);
    open.value = false;
};

const onKey = (e) => {
    if (!open.value || !results.value.length) return;
    if (e.key === 'ArrowDown') { highlighted.value = (highlighted.value + 1) % results.value.length; e.preventDefault(); }
    if (e.key === 'ArrowUp') { highlighted.value = (highlighted.value - 1 + results.value.length) % results.value.length; e.preventDefault(); }
    if (e.key === 'Enter') { pick(results.value[highlighted.value]); e.preventDefault(); }
};

const close = () => setTimeout(() => (open.value = false), 150);
const setText = (v) => (text.value = v);
defineExpose({ setText, text });
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="relative">
        <label v-if="label" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-navy-500">{{ label }}</label>
        <input
            v-model="text"
            type="text"
            autocomplete="off"
            :placeholder="placeholder"
            class="w-full rounded-xl border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600"
            :class="error && 'border-red-400'"
            @input="onInput"
            @focus="onFocus"
            @blur="close"
            @keydown="onKey"
        />
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
        <ul v-if="open && results.length" class="absolute z-30 mt-1 max-h-72 w-full min-w-[260px] overflow-auto rounded-xl border bg-white py-1 shadow-lg">
            <li
                v-for="(a, i) in results"
                :key="a.iata_code"
                class="flex cursor-pointer items-center justify-between gap-3 px-3 py-2 text-sm"
                :class="i === highlighted ? 'bg-navy-50' : 'hover:bg-navy-50/60'"
                @mousedown.prevent="pick(a)"
            >
                <span>
                    <span class="font-semibold text-navy-900">{{ a.city }}</span>
                    <span class="block text-xs text-navy-500">{{ a.name }}<span v-if="a.country_name"> · {{ a.country_name }}</span></span>
                </span>
                <span class="rounded bg-navy-900 px-1.5 py-0.5 text-xs font-bold text-white">{{ a.iata_code }}</span>
            </li>
        </ul>
    </div>
</template>
