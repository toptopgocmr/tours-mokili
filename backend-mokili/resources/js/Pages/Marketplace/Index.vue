<script setup>
import PhotoHero from '@/Components/PhotoHero.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const cat = computed(() => usePage().props.catalogue?.marketplace ?? {});
const f = reactive({
    q: props.filters.q ?? '', city: props.filters.city ?? '', category: props.filters.category ?? '',
    listing_type: props.filters.listing_type ?? '', condition: props.filters.condition ?? '',
    price_min: props.filters.price_min ?? '', price_max: props.filters.price_max ?? '', sort: props.filters.sort ?? '',
});
const ICONS = { vehicules: '🚗', immobilier: '🏠', telephones: '📱', electronique: '💻', maison: '🛋️', mode: '👗', enfants: '🧸', loisirs: '⚽', materiaux: '🧱', agriculture: '🌽', emploi: '💼', autres: '📦' };

const apply = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null));
    router.get('/marketplace', query, { preserveState: true, preserveScroll: true, replace: true });
};
const setCategory = (c) => {
    f.category = f.category === c ? '' : c;
    apply();
};
const reset = () => {
    Object.keys(f).forEach((k) => (f[k] = ''));
    apply();
};
</script>

<template>
    <Head title="Marketplace - petites annonces" />

    <PhotoHero class="px-6 py-8 text-white">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-gold-400">Marketplace</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">Achetez et vendez pres de chez vous</h1>
                    <p class="text-sm text-white/80">Petites annonces entre particuliers et professionnels, partout au Congo.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/marketplace/mes-annonces" class="btn-outline !border-white !text-white hover:!bg-white/10">Mes annonces</Link>
                    <Link href="/marketplace/deposer" class="btn-gold">+ Deposer une annonce</Link>
                </div>
            </div>
            <form class="mt-5 flex flex-col gap-2 rounded-2xl bg-white p-3 sm:flex-row" @submit.prevent="apply">
                <input v-model="f.q" type="text" placeholder="Que recherchez-vous ? (ex. iPhone, terrain, frigo...)" class="flex-1 rounded-full border-gray-200 text-sm text-navy-900" />
                <input v-model="f.city" type="text" placeholder="Ville" class="rounded-full border-gray-200 text-sm text-navy-900 sm:w-44" />
                <button type="submit" class="btn-gold sm:px-8">Rechercher</button>
            </form>
        </div>
    </PhotoHero>

    <div class="mx-auto max-w-6xl px-6 py-6">
        <div class="flex gap-2 overflow-x-auto pb-2">
            <button
                v-for="(label, key) in cat.categories"
                :key="key"
                type="button"
                class="flex shrink-0 flex-col items-center gap-1 rounded-2xl border px-3 py-2 text-xs font-semibold transition"
                :class="f.category === key ? 'border-pink-700 bg-pink-50 text-pink-700' : 'border-gray-200 text-navy-700 hover:border-pink-300'"
                @click="setCategory(key)"
            >
                <span class="text-xl">{{ ICONS[key] }}</span>{{ label }}
            </button>
        </div>

        <div class="mt-4 grid gap-6 lg:grid-cols-[220px_1fr]">
            <aside class="h-fit space-y-4 rounded-2xl border p-4 text-sm">
                <div>
                    <p class="font-semibold text-navy-900">Type d'annonce</p>
                    <label class="mt-1 flex items-center gap-2"><input v-model="f.listing_type" type="radio" value="" @change="apply" /> Toutes</label>
                    <label v-for="(label, key) in cat.listing_types" :key="key" class="flex items-center gap-2"><input v-model="f.listing_type" type="radio" :value="key" @change="apply" /> {{ label }}</label>
                </div>
                <div>
                    <p class="font-semibold text-navy-900">Etat</p>
                    <label class="mt-1 flex items-center gap-2"><input v-model="f.condition" type="radio" value="" @change="apply" /> Tous</label>
                    <label v-for="(label, key) in cat.conditions" :key="key" class="flex items-center gap-2"><input v-model="f.condition" type="radio" :value="key" @change="apply" /> {{ label }}</label>
                </div>
                <div>
                    <p class="font-semibold text-navy-900">Prix (XAF)</p>
                    <div class="mt-1 flex gap-2">
                        <input v-model="f.price_min" type="number" min="0" placeholder="Min" class="w-full rounded-lg border-gray-200 text-sm" />
                        <input v-model="f.price_max" type="number" min="0" placeholder="Max" class="w-full rounded-lg border-gray-200 text-sm" />
                    </div>
                </div>
                <button type="button" class="btn-console-primary w-full" @click="apply">Appliquer</button>
                <button type="button" class="w-full text-xs text-navy-500 underline" @click="reset">Reinitialiser</button>
            </aside>

            <div>
                <div class="mb-3 flex items-center justify-between text-sm text-navy-600">
                    <span>{{ products.total ?? products.data.length }} annonce(s)</span>
                    <select v-model="f.sort" class="rounded-lg border-gray-200 text-sm" @change="apply">
                        <option value="">Plus recentes</option>
                        <option value="price_asc">Prix croissant</option>
                        <option value="price_desc">Prix decroissant</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
                </div>
                <div v-if="!products.data.length" class="rounded-2xl border p-10 text-center">
                    <p class="font-semibold text-navy-900">Aucune annonce ne correspond a votre recherche.</p>
                    <Link href="/marketplace/deposer" class="btn-gold mt-4 inline-block">Deposer la premiere annonce</Link>
                </div>
                <Paginator :page="products" />
            </div>
        </div>

        <PartnersSection module="marketplace" title="Supermarches & boutiques en ligne" subtitle="Ou faire vos courses et vos achats en ligne au Congo." />
    </div>
</template>
