<script setup>
// Societes partenaires d'une rubrique (hotels dans Logement, compagnies
// aeriennes / bus / canots dans Voyage, taxis dans Voiture, restaurants et
// bars dans Divertissement, supermarches et e-commerce dans Marketplace).
import GuideCover from '@/Components/GuideCover.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    module: { type: String, required: true },
    title: { type: String, default: 'Nos partenaires' },
    subtitle: { type: String, default: '' },
    // Filtre initial (ex. "transport_routier" quand on choisit les circuits en bus).
    category: { type: String, default: '' },
    // Ne montrer QUE ces categories, separees par des virgules (ex. "compagnie_aerienne" sous l'onglet Vols).
    only: { type: String, default: '' },
});

const items = ref([]);
const active = ref('');

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/partners', { params: { module: props.module } });
        const only = props.only ? props.only.split(',') : [];
        items.value = (data.data ?? []).filter((b) => !only.length || only.includes(b.category));
        if (props.category && items.value.some((b) => b.category === props.category)) active.value = props.category;
    } catch {
        items.value = [];
    }
});

const groups = computed(() => {
    const map = new Map();
    items.value.forEach((b) => map.set(b.category, b.category_label));
    return [...map.entries()];
});

const visible = computed(() => (active.value ? items.value.filter((b) => b.category === active.value) : items.value));
</script>

<template>
    <section v-if="items.length" class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-navy-900">{{ title }}</h2>
                <p v-if="subtitle" class="mt-1 text-sm text-navy-500">{{ subtitle }}</p>
            </div>
            <div v-if="groups.length > 1" class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="!active ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700'"
                    @click="active = ''"
                >
                    Tous
                </button>
                <button
                    v-for="[key, label] in groups"
                    :key="key"
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="active === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700'"
                    @click="active = key"
                >
                    {{ label }}
                </button>
            </div>
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="b in visible"
                :key="b.id"
                :href="`/partenaires/${b.slug}`"
                class="group overflow-hidden rounded-2xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
            >
                <div class="relative h-32">
                    <GuideCover :src="b.image_url" :category="b.category" :alt="b.name" icon-class="h-10 w-10" />
                    <span v-if="b.stars" class="absolute right-2 top-2 rounded-full bg-navy-900/80 px-2 py-0.5 text-xs text-gold-400">{{ '★'.repeat(b.stars) }}</span>
                </div>
                <div class="p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600">{{ b.category_label }}</p>
                    <p class="font-semibold text-navy-900 group-hover:text-gold-600">{{ b.name }}</p>
                    <p class="text-xs text-navy-500">{{ b.district ?? b.city }}</p>
                </div>
            </Link>
        </div>
    </section>
</template>
