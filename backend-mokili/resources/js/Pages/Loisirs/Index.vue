<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    parks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Object, default: () => ({}) },
    cities: { type: Array, default: () => [] },
});

const q = ref(props.filters.q ?? '');
const ville = ref(props.filters.ville ?? '');
const type = ref(props.filters.type ?? '');

const apply = () =>
    router.get('/loisirs', { q: q.value || undefined, ville: ville.value || undefined, type: type.value || undefined }, { preserveState: true, preserveScroll: true });

const pickType = (key) => {
    type.value = type.value === key ? '' : key;
    apply();
};

const categoryColors = {
    parc_attractions: 'bg-amber-500',
    parc_aquatique: 'bg-sky-600',
    zoo: 'bg-emerald-700',
    aire_de_jeux: 'bg-pink-600',
    ile_loisirs: 'bg-cyan-700',
    parc_naturel: 'bg-green-800',
};

const price = (p) =>
    p.is_bookable ? `${Number(p.price_adult).toLocaleString('fr-FR')} ${p.currency}` : 'Tarif sur place';
</script>

<template>
    <Head title="Loisirs & Parcs au Congo" />

    <!-- Hero -->
    <section class="bg-navy-900 text-white">
        <div class="mx-auto max-w-7xl px-6 py-14">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">Congo-Brazzaville</p>
            <h1 class="mt-2 text-3xl font-bold md:text-4xl">Loisirs &amp; parcs d'attractions</h1>
            <p class="mt-2 max-w-2xl text-white/70">
                Parcs aquatiques, zoos, aires de jeux, iles et reserves naturelles : trouvez la sortie ideale a Brazzaville,
                Pointe-Noire et partout au Congo, et reservez vos billets en ligne.
            </p>

            <form class="mt-8 grid gap-3 rounded-2xl bg-white p-3 text-navy-900 shadow-xl md:grid-cols-[1fr_220px_auto]" @submit.prevent="apply">
                <input v-model="q" type="text" placeholder="Nom du parc, activite..." class="rounded-xl border-gray-200 text-sm" />
                <select v-model="ville" class="rounded-xl border-gray-200 text-sm" @change="apply">
                    <option value="">Toutes les villes</option>
                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                </select>
                <button type="submit" class="btn-gold !py-2.5">Rechercher</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <!-- Category chips -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="(label, key) in categories"
                :key="key"
                type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                :class="type === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-700 hover:border-navy-400'"
                @click="pickType(key)"
            >
                {{ label }}
            </button>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="p in parks.data"
                :key="p.id"
                :href="`/loisirs/${p.slug}`"
                class="group overflow-hidden rounded-2xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
            >
                <div class="relative">
                    <ListingImage :src="p.image_url" :fallback-class="categoryColors[p.category] ?? 'bg-navy-700'">
                        <span class="text-lg font-semibold">{{ p.name }}</span>
                    </ListingImage>
                    <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-semibold text-navy-900">{{ p.category_label }}</span>
                    <span v-if="p.is_featured" class="absolute right-3 top-3 rounded-full bg-gold-500 px-2.5 py-0.5 text-xs font-semibold text-navy-900">Coup de coeur</span>
                </div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900 group-hover:text-gold-600">{{ p.name }}</p>
                    <p class="mt-0.5 text-sm text-navy-500">{{ p.city }}<span v-if="p.district"> · {{ p.district }}</span></p>
                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-sm font-semibold" :class="p.is_bookable ? 'text-gold-600' : 'text-navy-400'">
                            <span v-if="p.is_bookable" class="font-normal text-navy-500">Des </span>{{ price(p) }}
                        </p>
                        <span class="text-xs text-navy-500">{{ p.opening_hours }}</span>
                    </div>
                </div>
            </Link>
        </div>

        <p v-if="!parks.data.length" class="mt-10 text-center text-navy-500">Aucun parc ne correspond a votre recherche.</p>

        <div v-if="parks.last_page > 1" class="mt-10 flex justify-center gap-2">
            <Link
                v-for="l in parks.links"
                :key="l.label"
                :href="l.url ?? ''"
                class="rounded-lg border px-3 py-1.5 text-sm"
                :class="[l.active ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700', !l.url && 'pointer-events-none opacity-40']"
                v-html="l.label"
            />
        </div>
    </div>
</template>
