<script setup>
// Selecteur de pays avec drapeau + indicatif, recherche, et groupes
// "Paiement disponible" / "Bientot disponible". Drapeaux SVG integres au
// site (flag-icons) : Windows n'affiche pas les emojis de drapeaux.
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import 'flag-icons/css/flag-icons.min.css';

const props = defineProps({
    modelValue: { type: String, default: '' },
    countries: { type: Array, default: () => [] }, // [{ code, name, dial_code, gateways: [] }]
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const q = ref('');
const root = ref(null);
const search = ref(null);

const flag = (code) => `fi fi-${String(code).toLowerCase()}`;
const current = computed(() => props.countries.find((c) => c.code === props.modelValue));
const norm = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const filtered = computed(() => {
    const t = norm(q.value).replace(/^\+/, '');
    return props.countries.filter((c) => !t || norm(c.name).includes(t) || String(c.dial_code).startsWith(t) || norm(c.code) === t);
});
const groups = computed(() => [
    ['Paiement disponible', filtered.value.filter((c) => c.gateways?.length)],
    ['Bientot disponible', filtered.value.filter((c) => !c.gateways?.length)],
].filter(([, list]) => list.length));

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        q.value = '';
        await nextTick();
        search.value?.focus();
    }
};
const pick = (c) => {
    emit('update:modelValue', c.code);
    open.value = false;
};
const onDoc = (e) => {
    if (root.value && !root.value.contains(e.target)) open.value = false;
};
onMounted(() => document.addEventListener('click', onDoc));
onBeforeUnmount(() => document.removeEventListener('click', onDoc));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-left transition hover:border-navy-400 focus:outline-none focus:ring-2 focus:ring-gold-400"
            @click.stop="toggle"
        >
            <span v-if="current" :class="flag(current.code)" class="!h-5 !w-7 shrink-0 rounded-sm bg-cover shadow-sm ring-1 ring-black/10" />
            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-navy-900">{{ current?.name ?? 'Choisir un pays' }}</span>
            <svg class="h-4 w-4 shrink-0 text-slate-500 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
        </button>

        <div v-if="open" class="absolute left-0 right-0 top-full z-40 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl" @click.stop>
            <div class="border-b border-slate-100 p-2">
                <input ref="search" v-model="q" type="text" placeholder="Rechercher un pays ou un indicatif (+242)" class="w-full rounded-lg border-slate-200 text-sm focus:border-gold-400 focus:ring-gold-400" />
            </div>
            <div class="max-h-72 overflow-y-auto py-1">
                <template v-for="[label, list] in groups" :key="label">
                    <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
                    <button
                        v-for="c in list"
                        :key="c.code"
                        type="button"
                        class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-navy-50"
                        :class="c.code === modelValue && 'bg-gold-50'"
                        @click="pick(c)"
                    >
                        <span :class="flag(c.code)" class="!h-4 !w-6 shrink-0 rounded-sm bg-cover ring-1 ring-black/10" />
                        <span class="min-w-0 flex-1 truncate" :class="c.gateways?.length ? 'text-navy-900' : 'text-slate-500'">{{ c.name }}</span>
                        <span class="shrink-0 tabular-nums text-xs text-slate-500">+{{ c.dial_code }}</span>
                        <svg v-if="c.code === modelValue" class="h-4 w-4 shrink-0 text-gold-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" /></svg>
                    </button>
                </template>
                <p v-if="!groups.length" class="px-3 py-4 text-center text-sm text-slate-500">Aucun pays trouve.</p>
            </div>
        </div>
    </div>
</template>
