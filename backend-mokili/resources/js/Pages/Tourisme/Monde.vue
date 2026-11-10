<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import GuideCover from '@/Components/GuideCover.vue';
import ParkCard from '@/Components/ParkCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

// TOURISME > Monde : comme les rubriques "Site touristique" / "Lieu de
// loisirs" de Boas, mais pour n'importe quelle ville du monde.
const props = defineProps({
    ville: { type: String, default: 'Brazzaville' },
    categorie: { type: String, default: '' },
    result: { type: Object, default: () => ({ city: null, places: [], error: null }) },
    categories: { type: Object, default: () => ({}) },
    cities: { type: Object, default: () => ({}) },
    partners: { type: Array, default: () => [] },
    parks: { type: Array, default: () => [] },
});

const q = ref(props.ville);
const cat = ref(props.categorie);
const region = ref(Object.keys(props.cities).find((r) => props.cities[r].includes(props.ville)) ?? Object.keys(props.cities)[0]);
const go = (ville) => router.get('/tourisme/monde', { ville }, { preserveScroll: false });
const submit = () => q.value.trim() && go(q.value.trim());

const counts = computed(() => props.result.places.reduce((m, p) => ({ ...m, [p.category]: (m[p.category] ?? 0) + 1 }), {}));
const places = computed(() => (cat.value ? props.result.places.filter((p) => p.category === cat.value) : props.result.places));
const cityName = computed(() => props.result.city?.name ?? props.ville);
const maps = (p) => `https://www.google.com/maps/search/?api=1&query=${p.lat},${p.lon}`;
const broken = ref([]);
</script>

<template>
    <Head :title="`Que visiter a ${cityName} ?`" />

    <PhotoHero class="px-4 pb-24 pt-10 text-white sm:pt-12">
        <div class="mx-auto max-w-6xl">
            <Link href="/divertissement" class="text-sm text-white/80 hover:text-white">&larr; Tourisme</Link>
            <h1 class="mt-2 text-3xl font-extrabold drop-shadow sm:text-4xl">Decouvrez le monde</h1>
            <p class="mt-1 text-white/90">Sites touristiques, musees, monuments, parcs et lieux de loisirs de toutes les villes du monde.</p>
        </div>
    </PhotoHero>

    <div class="relative z-10 -mt-16 px-4">
        <div class="mx-auto max-w-6xl rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgba(0,0,0,0.18)]">
            <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="submit">
                <input v-model="q" type="text" placeholder="Une ville du monde : Kinshasa, Paris, Dubai, Marrakech..." class="h-12 flex-1 rounded-xl border-slate-300 text-sm focus:border-gold-500 focus:ring-gold-500" />
                <button type="submit" class="h-12 rounded-xl bg-gold-500 px-10 font-bold text-navy-900 hover:bg-gold-400">Decouvrir</button>
            </form>
            <div class="mt-4 flex flex-wrap gap-2 border-b border-slate-100 pb-3">
                <button v-for="r in Object.keys(cities)" :key="r" type="button" class="rounded-full px-4 py-1.5 text-sm font-semibold transition" :class="region === r ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'" @click="region = r">{{ r }}</button>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button v-for="c in cities[region]" :key="c" type="button" class="rounded-full border px-3 py-1 text-sm transition" :class="cityName === c || ville === c ? 'border-gold-500 bg-gold-50 font-semibold text-navy-900' : 'border-slate-200 text-slate-700 hover:border-slate-400'" @click="go(c)">{{ c }}</button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <p v-if="result.error" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">{{ result.error }}</p>

        <template v-if="result.city">
            <!-- La ville -->
            <section class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white md:grid-cols-[2fr_3fr]">
                <img v-if="result.city.image" :src="result.city.image" :alt="cityName" class="h-56 w-full object-cover md:h-full" />
                <div class="p-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gold-600">Destination</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-navy-900">{{ cityName }}</h2>
                    <p class="mt-2 leading-relaxed text-slate-700">{{ result.city.summary }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
                        <Link :href="`/voyage?type=vol&destination=${encodeURIComponent(cityName)}`" class="rounded-full bg-navy-900 px-4 py-2 text-white hover:bg-navy-800">Vols vers {{ cityName }}</Link>
                        <Link :href="`/logement?city=${encodeURIComponent(cityName)}`" class="rounded-full border border-navy-200 px-4 py-2 text-navy-900 hover:bg-navy-50">Hotels a {{ cityName }}</Link>
                        <Link :href="`/voiture?type=location&city=${encodeURIComponent(cityName)}`" class="rounded-full border border-navy-200 px-4 py-2 text-navy-900 hover:bg-navy-50">Louer une voiture</Link>
                    </div>
                </div>
            </section>

            <!-- Partenaires MOKILI TOUR -->
            <section v-if="partners.length || parks.length" class="mt-10">
                <h2 class="text-xl font-extrabold text-navy-900">Avec nos partenaires a {{ cityName }}</h2>
                <div v-if="parks.length" class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><ParkCard v-for="p in parks" :key="p.id" :park="p" /></div>
                <div v-if="partners.length" class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-for="b in partners" :key="b.id" :href="`/partenaires/${b.slug}`" class="overflow-hidden rounded-2xl border bg-white transition hover:shadow-lg">
                        <div class="h-32"><GuideCover :src="b.image_url" :category="b.category" :alt="b.name" icon-class="h-10 w-10" /></div>
                        <div class="p-3"><p class="text-[11px] font-semibold uppercase text-gold-600">{{ b.category_label }}</p><p class="font-semibold text-navy-900">{{ b.name }}</p></div>
                    </Link>
                </div>
            </section>

            <!-- Lieux a visiter -->
            <section class="mt-10">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-2xl font-extrabold text-navy-900">Que visiter a {{ cityName }} ?</h2>
                    <p class="text-sm text-slate-500">{{ result.places.length }} lieu(x) a moins de 10 km</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold" :class="!cat ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 text-slate-700'" @click="cat = ''">Tout ({{ result.places.length }})</button>
                    <button v-for="(label, key) in categories" v-show="counts[key]" :key="key" type="button" class="rounded-full border px-4 py-1.5 text-sm font-semibold" :class="cat === key ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 text-slate-700'" @click="cat = key">{{ label }} ({{ counts[key] ?? 0 }})</button>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="p in places" v-show="!broken.includes(p.id)" :key="p.id" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg">
                        <img :src="p.image" :alt="p.name" class="h-52 w-full object-cover" loading="lazy" @error="broken.push(p.id)" />
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600">{{ categories[p.category] }}</p>
                            <h3 class="mt-0.5 text-lg font-bold leading-tight text-navy-900">{{ p.name }}</h3>
                            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ p.description }}</p>
                            <div class="mt-auto flex gap-2 pt-4 text-sm font-semibold">
                                <a :href="p.url" target="_blank" rel="noopener" class="flex-1 rounded-lg border border-slate-300 py-2 text-center text-navy-900 hover:bg-slate-50">En savoir plus</a>
                                <a v-if="p.lat" :href="maps(p)" target="_blank" rel="noopener" class="flex-1 rounded-lg bg-navy-900 py-2 text-center text-white hover:bg-navy-800">Itineraire</a>
                            </div>
                        </div>
                    </article>
                </div>
                <p v-if="!places.length" class="mt-6 rounded-2xl border bg-white p-8 text-center text-slate-600">Aucun lieu trouve dans cette categorie.</p>
                <p class="mt-6 text-xs text-slate-400">Textes et photos : Wikipedia / Wikimedia Commons (licence CC BY-SA). Verifiez les horaires d'ouverture avant votre visite.</p>
            </section>
        </template>
    </div>
</template>
