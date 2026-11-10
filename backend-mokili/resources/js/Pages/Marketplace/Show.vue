<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ContactButtons from '@/Components/ContactButtons.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    product: { type: Object, required: true },
    seller: { type: Object, default: null },
    isOwner: Boolean,
    similar: { type: Array, default: () => [] },
});

const p = computed(() => props.product);
const photos = computed(() => (p.value.gallery_urls?.length ? p.value.gallery_urls : [p.value.image_url].filter(Boolean)));
const current = ref(0);
const showPhone = ref(false);
const price = computed(() => (Number(p.value.price) > 0 ? `${Number(p.value.price).toLocaleString('fr-FR')} ${p.value.currency === 'XAF' ? 'FCFA' : p.value.currency}` : 'Prix a discuter'));
const STATUS = { draft: 'Brouillon', pending: 'En attente de validation', published: 'En ligne', rejected: 'Refusee' };
const toggleSold = () => router.post(`/marketplace/annonce/${p.value.id}/vendu`, {}, { preserveScroll: true });
</script>

<template>
    <Head :title="product.title" />
    <div class="mx-auto max-w-6xl px-6 py-8">
        <Link :href="`/marketplace?category=${p.category}`" class="text-sm text-navy-500 hover:text-navy-900">&larr; {{ p.category_label ?? 'Marketplace' }}</Link>

        <div v-if="isOwner" class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-navy-50 p-3 text-sm">
            <span>Votre annonce · <strong>{{ STATUS[p.status] }}</strong> · {{ p.views }} vue(s)<span v-if="p.rejection_reason"> · motif : {{ p.rejection_reason }}</span></span>
            <span class="flex gap-3">
                <Link :href="`/marketplace/annonce/${p.id}/modifier`" class="font-semibold text-navy-900 underline">Modifier</Link>
                <button type="button" class="font-semibold text-navy-900 underline" @click="toggleSold">{{ p.sold_at ? 'Remettre en ligne' : 'Marquer comme vendu' }}</button>
            </span>
        </div>

        <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_340px]">
            <div>
                <div class="overflow-hidden rounded-2xl bg-gray-100">
                    <img v-if="photos.length" :src="photos[current]" :alt="p.title" class="h-80 w-full object-contain sm:h-[420px]" />
                    <ListingImage v-else :src="null" fallback-class="bg-pink-700 text-xl font-semibold" class="h-72">{{ p.category_label ?? 'Annonce' }}</ListingImage>
                </div>
                <div v-if="photos.length > 1" class="mt-2 flex gap-2 overflow-x-auto">
                    <button v-for="(src, i) in photos" :key="src" type="button" class="h-16 w-20 shrink-0 overflow-hidden rounded-lg border-2" :class="i === current ? 'border-pink-700' : 'border-transparent'" @click="current = i">
                        <img :src="src" class="h-full w-full object-cover" />
                    </button>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-pink-700">{{ p.listing_type_label }} · {{ p.category_label }}</p>
                    <h1 class="text-2xl font-bold text-navy-900">{{ p.title }}</h1>
                    <p class="mt-2 text-2xl font-extrabold text-navy-900">{{ price }} <span v-if="p.negotiable" class="text-sm font-normal text-navy-500">· prix a debattre</span></p>
                    <p class="mt-1 text-sm text-navy-500">{{ p.city }}<span v-if="p.district"> · {{ p.district }}</span> · publiee le {{ new Date(p.created_at).toLocaleDateString('fr-FR') }}</p>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 rounded-2xl border p-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-navy-500">Categorie</dt><dd class="font-medium">{{ p.category_label }}</dd></div>
                    <div><dt class="text-navy-500">Etat</dt><dd class="font-medium capitalize">{{ p.condition }}</dd></div>
                    <div><dt class="text-navy-500">Localisation</dt><dd class="font-medium">{{ p.city }}</dd></div>
                </dl>

                <h2 class="mt-6 font-semibold text-navy-900">Description</h2>
                <p class="mt-2 whitespace-pre-line text-navy-700">{{ p.description }}</p>
            </div>

            <aside class="h-fit space-y-4 lg:sticky lg:top-24">
                <div class="rounded-2xl border p-5 shadow-sm">
                    <p class="font-semibold text-navy-900">{{ seller?.name ?? 'Vendeur' }}</p>
                    <p v-if="seller" class="text-xs text-navy-500">Membre depuis {{ seller.since }} · {{ seller.listings }} annonce(s) en ligne</p>
                    <div class="mt-4">
                        <button v-if="!showPhone" type="button" class="btn-gold w-full" @click="showPhone = true">Contacter le vendeur</button>
                        <template v-else>
                            <p class="mb-2 text-center text-lg font-bold text-navy-900">{{ p.contact_phone }}</p>
                            <ContactButtons :phone="p.contact_phone" :whatsapp="p.contact_whatsapp" :message="`Bonjour, votre annonce MOKILI TOUR « ${p.title} » est-elle toujours disponible ?`" />
                        </template>
                    </div>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4 text-xs text-amber-900">
                    <p class="font-semibold">Conseils de securite</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4">
                        <li>Rencontrez le vendeur dans un lieu public.</li>
                        <li>Verifiez l'article avant de payer.</li>
                        <li>Ne payez jamais d'avance a un inconnu.</li>
                    </ul>
                </div>
            </aside>
        </div>

        <section v-if="similar.length" class="mt-12">
            <h2 class="text-lg font-bold text-navy-900">Annonces similaires</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                <ProductCard v-for="s in similar" :key="s.id" :product="s" />
            </div>
        </section>
    </div>
</template>
