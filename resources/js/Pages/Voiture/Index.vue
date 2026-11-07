<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import PartnersSection from '@/Components/PartnersSection.vue';
import ListingImage from '@/Components/ListingImage.vue';
import GuideCover from '@/Components/GuideCover.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    vehicles: { type: Object, required: true },
    garages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    offerTypes: { type: Object, default: () => ({}) },
});

const search = reactive({ type: props.filters.type ?? '', city: props.filters.city ?? '', q: props.filters.q ?? '' });
const ICONS = { location: '🔑', vente: '🏷️', mecanique: '🔧' };
const HERO = {
    '': ['Location, vente et mecanique', 'Louez, achetez ou faites entretenir votre vehicule.'],
    location: ['Location de vehicules', 'Citadines, SUV et utilitaires, avec ou sans chauffeur.'],
    vente: ['Vehicules a vendre', 'Annonces de particuliers et de professionnels.'],
    mecanique: ['Mecanique & entretien', 'Garages, vidange, diagnostic, carrosserie, depannage.'],
};

const submit = () => {
    const query = Object.fromEntries(Object.entries(search).filter(([, v]) => v));
    router.get('/voiture', query, { preserveState: true, preserveScroll: true, replace: true });
};
const setType = (t) => {
    search.type = t;
    submit();
};
const price = (v) => (v.display_price ? `${v.display_price.suffix === 'a partir de' ? 'Des ' : ''}${Number(v.display_price.amount).toLocaleString('fr-FR')} ${v.currency}${v.display_price.suffix === '/ jour' ? ' / jour' : ''}` : 'Prix sur demande');
</script>

<template>
    <Head title="Voiture" />

    <section class="relative h-[240px] overflow-hidden sm:h-[280px]">
        <img src="/images/hero.jpg" alt="Voiture" class="h-full w-full object-cover object-[center_40%]" />
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        <div class="relative max-w-3xl px-6 pt-14 sm:px-10">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">Voiture</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white drop-shadow sm:text-3xl">{{ HERO[search.type]?.[0] }}</h1>
            <p class="mt-1 text-sm text-white/90 drop-shadow">{{ HERO[search.type]?.[1] }}</p>
        </div>
    </section>

    <div class="relative z-10 -mt-10 px-6 sm:-mt-12">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-4 shadow-[0_4px_16px_rgba(0,0,0,0.15)] sm:p-5">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-3">
                <button type="button" class="rounded-full px-4 py-1.5 text-sm font-semibold transition" :class="!search.type ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'" @click="setType('')">Tout</button>
                <button
                    v-for="(label, key) in offerTypes"
                    :key="key"
                    type="button"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="search.type === key ? 'bg-navy-900 text-white' : 'text-navy-700 hover:bg-navy-50'"
                    @click="setType(key)"
                >
                    {{ ICONS[key] }} {{ label }}
                </button>
            </div>
            <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="submit">
                <input v-model="search.q" type="text" placeholder="Marque, modele (ex. Toyota)..." class="flex-1 rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600" />
                <input v-model="search.city" type="text" placeholder="Ville" class="rounded-full border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600 sm:w-48" />
                <button type="submit" class="btn-gold sm:px-10">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10">
        <section v-if="garages.length" class="mb-12">
            <h2 class="text-xl font-bold text-navy-900">Garages partenaires</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="g in garages" :key="g.id" :href="`/partenaires/${g.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                    <div class="h-28"><GuideCover :src="g.image_url" category="garage" :alt="g.name" icon-class="h-10 w-10" /></div>
                    <div class="p-3">
                        <p class="font-semibold text-navy-900">{{ g.name }}</p>
                        <p class="text-xs text-navy-500">{{ g.district ?? g.city }}</p>
                    </div>
                </Link>
            </div>
        </section>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="v in vehicles.data" :key="v.id" :href="`/voiture/${v.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <div class="relative">
                    <ListingImage :src="v.image_url" fallback-class="bg-orange-600">{{ v.brand }} {{ v.model }}</ListingImage>
                    <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-semibold text-navy-900">{{ ICONS[v.offer_type] }} {{ v.offer_type_label }}</span>
                </div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ v.title }}</p>
                    <p class="text-xs text-navy-500">
                        {{ [v.year, v.mileage_km ? `${Number(v.mileage_km).toLocaleString('fr-FR')} km` : null, v.fuel, v.city].filter(Boolean).join(' · ') }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ price(v) }}</p>
                </div>
            </Link>
        </div>
        <p v-if="!vehicles.data.length" class="mt-10 text-center text-navy-500">Aucune annonce pour le moment dans cette categorie.</p>
        <Paginator :page="vehicles" />

        <PartnersSection v-if="search.type !== 'mecanique'" module="voiture" title="Taxis, VTC & garages" subtitle="Applications de taxi, chauffeurs prives et garages a Brazzaville." />
    </div>
</template>
