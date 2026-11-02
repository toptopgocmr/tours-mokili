<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    business: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
});

const b = computed(() => props.business);
const mapsUrl = computed(() =>
    b.value.latitude && b.value.longitude
        ? `https://www.google.com/maps?q=${b.value.latitude},${b.value.longitude}`
        : `https://www.google.com/maps/search/${encodeURIComponent(`${b.value.name} ${b.value.address ?? ''} ${b.value.city} Congo`)}`,
);
const MODULE_PAGES = {
    logement: ['/logement', 'Logement'],
    voyage: ['/voyage', 'Voyage'],
    voiture: ['/voiture', 'Voiture'],
    divertissement: ['/divertissement', 'Divertissement'],
    marketplace: ['/marketplace', 'Marketplace'],
};
const back = computed(() => MODULE_PAGES[b.value.module] ?? ['/', 'Accueil']);
const whatsappUrl = computed(() => (b.value.whatsapp ? `https://wa.me/${b.value.whatsapp.replace(/\D/g, '')}` : null));
</script>

<template>
    <Head :title="business.name" />

    <div class="mx-auto max-w-6xl px-6 py-10">
        <Link :href="back[0]" class="text-sm text-navy-500 hover:text-navy-900">&larr; {{ back[1] }}</Link>

        <div class="mt-4 grid gap-10 md:grid-cols-[1fr_340px]">
            <div>
                <div class="relative h-72 overflow-hidden rounded-3xl">
                    <GuideCover :src="business.image_url" :category="business.category" :alt="business.name" icon-class="h-20 w-20" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/80 via-transparent to-transparent" />
                    <div class="absolute bottom-5 left-6 right-6 text-white">
                        <span class="rounded-full bg-gold-500 px-3 py-0.5 text-xs font-semibold text-navy-900">{{ business.category_label }}</span>
                        <h1 class="mt-2 text-3xl font-bold">{{ business.name }}</h1>
                        <p class="text-white/80">
                            <span v-if="business.stars" class="text-gold-400">{{ '★'.repeat(business.stars) }} · </span>{{ business.district ?? business.city }}
                        </p>
                    </div>
                </div>

                <div v-if="business.gallery_urls?.length" class="mt-4 flex gap-3 overflow-x-auto pb-2">
                    <img v-for="(g, i) in business.gallery_urls" :key="i" :src="g" :alt="`${business.name} ${i + 1}`" class="h-24 w-36 shrink-0 rounded-xl object-cover" loading="lazy" />
                </div>

                <p class="mt-6 whitespace-pre-line leading-relaxed text-navy-700">{{ business.description }}</p>

                <div v-if="business.services?.length" class="mt-6">
                    <h2 class="font-semibold text-navy-900">Services</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="s in business.services" :key="s" class="rounded-full bg-navy-50 px-3 py-1 text-sm text-navy-700">{{ s }}</span>
                    </div>
                </div>
            </div>

            <aside class="h-fit space-y-4 rounded-3xl border p-6 shadow-sm md:sticky md:top-28">
                <dl class="space-y-3 text-sm">
                    <div v-if="business.address"><dt class="text-navy-500">Adresse</dt><dd class="font-medium">{{ business.address }}, {{ business.city }}</dd></div>
                    <div v-if="business.opening_hours"><dt class="text-navy-500">Horaires</dt><dd class="font-medium">{{ business.opening_hours }}</dd></div>
                    <div v-if="business.price_range"><dt class="text-navy-500">Tarifs</dt><dd class="font-medium">{{ business.price_range }}</dd></div>
                    <div v-if="business.phone"><dt class="text-navy-500">Telephone</dt><dd class="font-medium">{{ business.phone }}</dd></div>
                    <div v-if="business.email"><dt class="text-navy-500">Email</dt><dd class="font-medium">{{ business.email }}</dd></div>
                </dl>
                <a v-if="business.phone" :href="`tel:${business.phone.replace(/\s/g, '')}`" class="btn-gold block w-full text-center">Appeler</a>
                <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">WhatsApp</a>
                <a v-if="business.website" :href="business.website" target="_blank" rel="noopener" class="btn-outline block w-full text-center">Site web</a>
                <a :href="mapsUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">Itineraire</a>
                <Link v-if="business.category === 'hotel'" href="/logement" class="block text-center text-sm font-semibold text-gold-600 hover:underline">Reserver un logement avec MOKILI TOUR →</Link>
                <Link v-if="business.category === 'compagnie_aerienne'" href="/voyage" class="block text-center text-sm font-semibold text-gold-600 hover:underline">Voir nos offres de vols →</Link>
            </aside>
        </div>

        <section v-if="similar.length" class="mt-14">
            <h2 class="text-xl font-bold text-navy-900">Autres adresses : {{ business.category_label }}</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-4">
                <Link v-for="s in similar" :key="s.id" :href="`/partenaires/${s.slug}`" class="overflow-hidden rounded-2xl border transition hover:shadow-md">
                    <div class="h-24"><GuideCover :src="s.image_url" :category="s.category" :alt="s.name" icon-class="h-8 w-8" /></div>
                    <p class="p-3 text-sm font-semibold text-navy-900">{{ s.name }}</p>
                </Link>
            </div>
        </section>
    </div>
</template>
