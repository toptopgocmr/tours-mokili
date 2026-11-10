<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ContactButtons from '@/Components/ContactButtons.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({
    vehicle: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
});

const v = computed(() => props.vehicle);
const price = computed(() => (v.value.display_price
    ? `${v.value.display_price.suffix === 'a partir de' ? 'A partir de ' : ''}${Number(v.value.display_price.amount).toLocaleString('fr-FR')} ${v.value.currency}${v.value.display_price.suffix === '/ jour' ? ' / jour' : ''}`
    : 'Prix sur demande'));
</script>

<template>
    <Head :title="vehicle.title" />
    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 md:grid-cols-[1fr_320px]">
        <div>
            <Link :href="`/voiture?type=${v.offer_type}`" class="text-sm text-navy-500 hover:text-navy-900">&larr; {{ v.offer_type_label }}</Link>
            <div class="mt-2 overflow-hidden rounded-2xl">
                <ListingImage :src="v.image_url" fallback-class="bg-orange-600 text-xl font-semibold" class="h-64">{{ v.brand }} {{ v.model }}</ListingImage>
            </div>
            <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ v.title }}</h1>
            <p v-if="v.description" class="mt-3 whitespace-pre-line text-navy-700">{{ v.description }}</p>
            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-navy-500">Annonce</dt><dd class="font-medium">{{ v.offer_type_label }}</dd></div>
                <div><dt class="text-navy-500">Marque / modele</dt><dd class="font-medium">{{ v.brand }} {{ v.model }}</dd></div>
                <div v-if="v.year"><dt class="text-navy-500">Annee</dt><dd class="font-medium">{{ v.year }}</dd></div>
                <div v-if="v.mileage_km"><dt class="text-navy-500">Kilometrage</dt><dd class="font-medium">{{ Number(v.mileage_km).toLocaleString('fr-FR') }} km</dd></div>
                <div v-if="v.offer_type !== 'mecanique'"><dt class="text-navy-500">Boite</dt><dd class="font-medium capitalize">{{ v.transmission }}</dd></div>
                <div v-if="v.fuel"><dt class="text-navy-500">Carburant</dt><dd class="font-medium capitalize">{{ v.fuel }}</dd></div>
                <div v-if="v.city"><dt class="text-navy-500">Ville</dt><dd class="font-medium">{{ v.city }}</dd></div>
            </dl>
            <div v-if="v.services?.length" class="mt-6">
                <p class="text-sm font-semibold text-navy-900">Prestations</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span v-for="s in v.services" :key="s" class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">{{ s }}</span>
                </div>
            </div>
        </div>
        <aside class="h-fit space-y-4 rounded-2xl border p-6 shadow-sm">
            <PromoBadge :promo="v.promo" show-end class="mb-1" />
            <p class="text-2xl font-bold text-gold-600"><span v-if="v.display_price?.original" class="mr-1 text-base font-normal text-navy-400 line-through">{{ Number(v.display_price.original).toLocaleString('fr-FR') }}</span>{{ price }}</p>
            <ContactButtons :phone="v.phone" :message="`Bonjour, je suis interesse(e) par votre annonce MOKILI TOUR : ${v.title}`" />
        </aside>

        <section v-if="similar.length" class="md:col-span-2">
            <h2 class="text-lg font-bold text-navy-900">Annonces similaires</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <Link v-for="s in similar" :key="s.id" :href="`/voiture/${s.slug}`" class="rounded-2xl border p-4 transition hover:shadow-md">
                    <p class="font-semibold text-navy-900">{{ s.title }}</p>
                    <p class="text-xs text-navy-500">{{ s.city }}</p>
                </Link>
            </div>
        </section>
    </div>
</template>
